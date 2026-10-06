<?php
namespace mod_videotrackerpremium\task;

use context_module;
use core\task\adhoc_task;
use mod_videotrackerpremium\local\service\bulk_service;

/**
 * Applies a large validated operational action outside the request cycle.
 */
class bulk_action_batch extends adhoc_task {
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

        bulk_service::execute(
            $activity,
            context_module::instance($cm->id),
            array_map('intval', (array)$data->userids),
            clean_param((string)$data->action, PARAM_ALPHA),
            (int)$data->value,
            (int)$data->actorid
        );
    }
}
