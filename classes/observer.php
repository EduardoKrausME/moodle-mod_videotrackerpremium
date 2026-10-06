<?php
namespace mod_videotrackerpremium;

use context_module;
use mod_videotrackerpremium\local\service\progress_service;

/**
 * Observes public Video Bridge progress events.
 */
class observer {
    public static function progress_threshold_reached(
        \local_video_bridge\event\progress_threshold_reached $event
    ): void {
        global $DB;

        $other = $event->other;
        if (($other['component'] ?? '') !== 'mod_videotrackerpremium') {
            return;
        }

        $activityid = (int)($other['itemid'] ?? 0);
        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => $activityid],
            '*',
            IGNORE_MISSING
        );
        if (!$activity || (int)($other['threshold'] ?? 0) < (int)$activity->minimumpercent) {
            return;
        }

        $context = context_module::instance_by_id($event->contextid, IGNORE_MISSING);
        if (!$context || empty($event->relateduserid)) {
            return;
        }
        progress_service::sync_completion(
            $activity,
            $context,
            (int)$event->relateduserid
        );
    }

    public static function video_completed(
        \local_video_bridge\event\video_completed $event
    ): void {
        global $DB;

        $other = $event->other;
        if (($other['component'] ?? '') !== 'mod_videotrackerpremium') {
            return;
        }

        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => (int)($other['itemid'] ?? 0)],
            '*',
            IGNORE_MISSING
        );
        if (!$activity || empty($event->relateduserid)) {
            return;
        }

        $context = context_module::instance_by_id($event->contextid, IGNORE_MISSING);
        if ($context) {
            progress_service::sync_completion(
                $activity,
                $context,
                (int)$event->relateduserid
            );
        }
    }
}
