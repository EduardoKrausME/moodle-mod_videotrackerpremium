<?php
namespace mod_videotrackerpremium\task;

use context_module;
use core\task\scheduled_task;
use mod_videotrackerpremium\local\service\progress_service;
use mod_videotrackerpremium\local\service\reminder_service;
use mod_videotrackerpremium\local\service\recipient_guard;

/**
 * Synchronises operational state and processes due reminders.
 */
class process_reminders extends scheduled_task {
    public function get_name(): string {
        return get_string('taskprocessreminders', 'videotrackerpremium');
    }

    public function execute(): void {
        global $DB;

        foreach ($DB->get_records('videotrackerpremium') as $activity) {
            $cm = get_coursemodule_from_instance(
                'videotrackerpremium',
                $activity->id,
                $activity->course,
                false,
                IGNORE_MISSING
            );
            if (!$cm) {
                continue;
            }

            $context = context_module::instance($cm->id);
            progress_service::ensure_threshold($activity, $context);
            $users = recipient_guard::get_eligible_users($context, 'u.id');
            $userids = array_map('intval', array_keys($users));

            if (count($userids) > 100) {
                foreach (array_chunk($userids, 100) as $chunk) {
                    $task = new sync_activity_batch();
                    $task->set_custom_data([
                        'activityid' => (int)$activity->id,
                        'userids' => $chunk,
                    ]);
                    \core\task\manager::queue_adhoc_task($task, true);
                }
            } else {
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

        $due = reminder_service::claim_due(200);
        if (count($due) > 50) {
            foreach (array_chunk($due, 50) as $chunk) {
                $task = new send_reminder_batch();
                $task->set_custom_data(['notificationids' => $chunk]);
                \core\task\manager::queue_adhoc_task($task);
            }
        } else {
            foreach ($due as $notificationid) {
                reminder_service::send($notificationid);
            }
        }
    }
}
