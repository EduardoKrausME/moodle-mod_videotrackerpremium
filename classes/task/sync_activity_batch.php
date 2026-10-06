<?php
namespace mod_videotrackerpremium\task;

use context_module;
use core\task\adhoc_task;
use mod_videotrackerpremium\local\service\progress_service;
use mod_videotrackerpremium\local\service\reminder_service;

/**
 * Synchronises a bounded learner batch outside page loads.
 */
class sync_activity_batch extends adhoc_task {
    public function execute(): void {
        global $DB;

        $data = $this->get_custom_data();
        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => (int)$data->activityid],
            '*',
            IGNORE_MISSING
        );
        if (!$activity) {
            return;
        }

        $cm = get_coursemodule_from_instance(
            'videotrackerpremium',
            $activity->id,
            $activity->course,
            false,
            IGNORE_MISSING
        );
        if (!$cm) {
            return;
        }

        $context = context_module::instance($cm->id);
        $userids = array_values(array_unique(array_map('intval', (array)$data->userids)));
        $progresses = progress_service::get_progress_batch($activity, $context, $userids);

        foreach ($userids as $userid) {
            progress_service::sync_completion(
                $activity,
                $context,
                $userid,
                $progresses[$userid] ?? null
            );
            reminder_service::synchronise_user($activity, $context, $userid);
        }
    }
}
