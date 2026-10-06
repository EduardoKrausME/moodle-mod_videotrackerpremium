<?php
// This file is part of Moodle - http://moodle.org/.

use local_video_bridge\progress\manager as bridge_progress;
use local_video_bridge\source\manager as source_manager;
use mod_videotrackerpremium\local\service\reminder_service;

defined('MOODLE_INTERNAL') || die;

/**
 * Declares supported Moodle features.
 *
 * @param string $feature
 * @return bool|null
 */
function videotrackerpremium_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_RESOURCE,
        FEATURE_GROUPS => true,
        FEATURE_GROUPINGS => true,
        FEATURE_MOD_INTRO => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_CONTENT,
        default => null,
    };
}

/**
 * Builds the persisted reminder configuration from virtual form fields.
 *
 * @param stdClass $data
 * @return void
 */
function videotrackerpremium_normalise_reminders(stdClass $data): void {
    $offsets = [];
    foreach ([7, 3, 1] as $days) {
        $field = 'reminder' . $days;
        if (!empty($data->{$field})) {
            $offsets[] = -$days;
        }
        unset($data->{$field});
    }
    if (!empty($data->reminderday)) {
        $offsets[] = 0;
    }
    unset($data->reminderday);
    if (!empty($data->reminderafter1)) {
        $offsets[] = 1;
    }
    unset($data->reminderafter1);

    sort($offsets, SORT_NUMERIC);
    $config = [
        'sendavailable' => !empty($data->sendavailable),
        'offsets' => array_values(array_unique($offsets)),
        'repeatlateevery' => max(0, (int)($data->repeatlateevery ?? 0)),
    ];
    unset($data->sendavailable, $data->repeatlateevery);
    $data->reminderconfig = json_encode($config, JSON_THROW_ON_ERROR);
}

/**
 * Adds an activity instance.
 *
 * @param stdClass $data
 * @param mod_videotrackerpremium_mod_form|null $mform
 * @return int
 */
function videotrackerpremium_add_instance(stdClass $data, ?mod_videotrackerpremium_mod_form $mform = null): int {
    global $DB;

    videotrackerpremium_normalise_reminders($data);
    (new source_manager())->normalise_record($data);
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $id = $DB->insert_record('videotrackerpremium', $data);
    $data->id = $id;

    if (!empty($data->coursemodule)) {
        $context = context_module::instance((int)$data->coursemodule);
        (new source_manager())->save_files($data, $context);
        bridge_progress::set_threshold(
            $context->id,
            'mod_videotrackerpremium',
            $id,
            bridge_progress::media_hash(
                (string)$data->videosource,
                (string)$data->sourceconfig
            ),
            (int)$data->minimumpercent,
            'mod_videotrackerpremium'
        );
    }
    return $id;
}

/**
 * Updates an activity instance.
 *
 * @param stdClass $data
 * @param mod_videotrackerpremium_mod_form|null $mform
 * @return bool
 */
function videotrackerpremium_update_instance(stdClass $data, ?mod_videotrackerpremium_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $previous = $DB->get_record('videotrackerpremium', ['id' => $data->id], '*', MUST_EXIST);
    videotrackerpremium_normalise_reminders($data);
    (new source_manager())->normalise_record($data);

    $previoushash = bridge_progress::media_hash(
        (string)$previous->videosource,
        (string)$previous->sourceconfig
    );
    $newhash = bridge_progress::media_hash(
        (string)$data->videosource,
        (string)$data->sourceconfig
    );
    $mediachanged = $previoushash !== $newhash;
    $thresholdchanged = (int)$previous->minimumpercent !== (int)$data->minimumpercent;

    $data->timemodified = time();
    $result = $DB->update_record('videotrackerpremium', $data);

    if (!empty($data->coursemodule)) {
        $context = context_module::instance((int)$data->coursemodule);
        (new source_manager())->save_files($data, $context, (string)$previous->videosource);

        if ($mediachanged) {
            bridge_progress::delete_consumer_media(
                $context->id,
                'mod_videotrackerpremium',
                (int)$data->id,
                $previoushash
            );
        }

        if ($mediachanged || $thresholdchanged) {
            bridge_progress::set_threshold(
                $context->id,
                'mod_videotrackerpremium',
                (int)$data->id,
                $newhash,
                (int)$data->minimumpercent,
                'mod_videotrackerpremium'
            );
            $DB->delete_records('vtrackpremium_state', ['activityid' => (int)$data->id]);

            $cm = get_coursemodule_from_id(
                'videotrackerpremium',
                (int)$data->coursemodule,
                0,
                false,
                MUST_EXIST
            );
            $course = get_course($cm->course);
            (new completion_info($course))->reset_all_state($cm);
        }
    }

    reminder_service::cancel_activity_pending(
        (int)$data->id,
        $mediachanged ? 'mediachanged' : 'activityupdated'
    );
    return $result;
}

/**
 * Deletes an activity and operational records.
 *
 * @param int $id
 * @return bool
 */
function videotrackerpremium_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('videotrackerpremium', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videotrackerpremium', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        (new source_manager())->delete_files($context);
        bridge_progress::delete_consumer(
            $context->id,
            'mod_videotrackerpremium',
            (int)$activity->id
        );
    }

    $transaction = $DB->start_delegated_transaction();
    foreach (['vtrackpremium_notify', 'vtrackpremium_history', 'vtrackpremium_override', 'vtrackpremium_state'] as $table) {
        $DB->delete_records($table, ['activityid' => $id]);
    }
    $DB->delete_records('videotrackerpremium', ['id' => $id]);
    $transaction->allow_commit();
    return true;
}

/**
 * Returns effective activity dates for course pages.
 *
 * @param cm_info $cm
 * @return cached_cm_info
 */
function videotrackerpremium_get_coursemodule_info($cm) {
    global $DB;

    $info = new cached_cm_info();
    $activity = $DB->get_record('videotrackerpremium', ['id' => $cm->instance], 'id,name,availablefrom,deadline');
    if (!$activity) {
        return $info;
    }
    $info->name = $activity->name;
    return $info;
}
