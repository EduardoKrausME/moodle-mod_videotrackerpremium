<?php
namespace mod_videotrackerpremium\local\service;

use completion_info;
use context_module;
use local_video_bridge\progress\manager as bridge_progress;
use mod_videotrackerpremium\event\activity_completed_late;
use stdClass;

/**
 * Reads authoritative Video Bridge progress and projects it into operational state.
 */
class progress_service {
    public static function media_hash(stdClass $activity): string {
        if (method_exists(bridge_progress::class, 'media_hash')) {
            return bridge_progress::media_hash((string)$activity->videosource, (string)$activity->sourceconfig);
        }
        return hash('sha256', $activity->videosource . '|' . $activity->sourceconfig);
    }

    public static function get_progress_batch(stdClass $activity, context_module $context, array $userids): array {
        $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
        if (!$userids) {
            return [];
        }
        $hash = self::media_hash($activity);
        $records = bridge_progress::get_progress_batch(
            $context->id,
            'mod_videotrackerpremium',
            (int)$activity->id,
            $hash,
            $userids
        );
        $sessions = bridge_progress::get_latest_session_times(
            $context->id,
            'mod_videotrackerpremium',
            (int)$activity->id,
            $hash,
            $userids
        );
        foreach ($records as $userid => $record) {
            $record->lastsession = (int)($sessions[(int)$userid] ?? 0);
        }
        return $records;
    }

    public static function ensure_threshold(stdClass $activity, context_module $context): void {
        $arguments = [
            $context->id,
            'mod_videotrackerpremium',
            (int)$activity->id,
            self::media_hash($activity),
            (int)$activity->minimumpercent,
            'mod_videotrackerpremium',
        ];

        if (method_exists(bridge_progress::class, 'set_threshold')) {
            bridge_progress::set_threshold(...$arguments);
            return;
        }
        if (method_exists(bridge_progress::class, 'register_threshold')) {
            bridge_progress::register_threshold(...$arguments);
        }
    }

    public static function sync_completion(
        stdClass $activity,
        context_module $context,
        int $userid,
        ?stdClass $progress = null
    ): stdClass {
        global $DB;

        if ($progress === null) {
            $batch = self::get_progress_batch($activity, $context, [$userid]);
            $progress = $batch[$userid] ?? null;
        }
        $percent = $progress ? (int)$progress->percent : 0;
        $now = time();

        $state = $DB->get_record('vtrackpremium_state', [
            'activityid' => $activity->id,
            'userid' => $userid,
        ]);
        if (!$state) {
            $state = (object)[
                'activityid' => (int)$activity->id,
                'userid' => $userid,
                'completed' => 0,
                'completiontime' => 0,
                'completionpercent' => 0,
                'laststatus' => 'notstarted',
                'lastaccess' => 0,
                'timemodified' => $now,
            ];
            $state->id = $DB->insert_record('vtrackpremium_state', $state);
        }

        $override = $DB->get_record('vtrackpremium_override', [
            'activityid' => $activity->id,
            'userid' => $userid,
        ]);
        if ($override && !empty($override->waived)) {
            $state->laststatus = 'waived';
            $state->timemodified = $now;
            $DB->update_record('vtrackpremium_state', $state);
            reminder_service::cancel_user_pending((int)$activity->id, $userid, 'waived');
            return $state;
        }

        if (!$state->completed && $percent >= (int)$activity->minimumpercent) {
            $factory = \core\lock\lock_config::get_lock_factory('mod_videotrackerpremium');
            $lock = $factory->get_lock(
                'completion_' . (int)$activity->id . '_' . $userid,
                5
            );
            if (!$lock) {
                return $state;
            }

            try {
                $lateststate = $DB->get_record('vtrackpremium_state', [
                    'activityid' => $activity->id,
                    'userid' => $userid,
                ]);
                if ($lateststate && !empty($lateststate->completed)) {
                    return $lateststate;
                }
                if ($lateststate) {
                    $state = $lateststate;
                }

                $completiontime = $progress && !empty($progress->timemodified)
                    ? (int)$progress->timemodified
                    : $now;
                $state->completed = 1;
                $state->completiontime = $completiontime;
                $state->completionpercent = $percent;
                $state->laststatus = 'completed';
                $state->timemodified = $now;
                $DB->update_record('vtrackpremium_state', $state);
                $DB->insert_record('vtrackpremium_history', (object)[
                    'activityid' => (int)$activity->id,
                    'userid' => $userid,
                    'actorid' => 0,
                    'action' => 'completed',
                    'olddeadline' => 0,
                    'newdeadline' => 0,
                    'details' => 'percent=' . $percent,
                    'timecreated' => $completiontime,
                ]);

                reminder_service::cancel_user_pending((int)$activity->id, $userid, 'completed');
                if (!empty($activity->remindersenabled)) {
                    reminder_service::queue_completion_confirmation($activity, $userid, $now);
                }

                if (!empty($activity->completionrequired)) {
                    $cm = get_coursemodule_from_instance(
                        'videotrackerpremium',
                        $activity->id,
                        $activity->course,
                        false,
                        MUST_EXIST
                    );
                    $course = get_course($activity->course);
                    (new completion_info($course))->update_state(
                        $cm,
                        COMPLETION_UNKNOWN,
                        $userid,
                        true
                    );
                }

                $deadline = status_service::effective_deadline($activity, $override);
                if ($deadline > 0 && $completiontime > $deadline) {
                    $event = activity_completed_late::create([
                        'objectid' => $state->id,
                        'context' => $context,
                        'relateduserid' => $userid,
                        'other' => [
                            'activityid' => (int)$activity->id,
                            'deadline' => $deadline,
                            'completiontime' => $completiontime,
                            'percent' => $percent,
                        ],
                    ]);
                    $event->trigger();
                }
            } finally {
                $lock->release();
            }
        } else {
            $state->completionpercent = max((int)$state->completionpercent, $percent);
            $state->timemodified = $now;
            $DB->update_record('vtrackpremium_state', $state);
        }

        return $state;
    }
}
