<?php
namespace mod_videotrackerpremium\local\service;

use context_module;
use stdClass;

/**
 * Operational status and compliance classification.
 */
class status_service {
    public static function effective_deadline(stdClass $activity, ?stdClass $override): int {
        if ($override && !empty($override->deadline)) {
            return (int)$override->deadline;
        }
        return (int)$activity->deadline;
    }

    public static function classify(
        int $percent,
        int $required,
        int $now,
        int $availablefrom,
        int $deadline,
        bool $waived = false,
        bool $extended = false,
        bool $completed = false
    ): string {
        if ($waived) {
            return 'waived';
        }
        if ($completed || $percent >= $required) {
            return 'completed';
        }
        if ($availablefrom > 0 && $now < $availablefrom) {
            return 'notavailable';
        }
        if ($deadline > 0) {
            if ($now > $deadline) {
                return 'overdue';
            }
            if (userdate($now, '%Y%m%d') === userdate($deadline, '%Y%m%d')) {
                return 'duetoday';
            }
            if (($deadline - $now) <= (3 * DAYSECS)) {
                return 'duesoon';
            }
        }
        if ($extended) {
            return 'extended';
        }
        return $percent > 0 ? 'inprogress' : 'notstarted';
    }

    public static function get_user_status(
        stdClass $activity,
        context_module $context,
        int $userid,
        ?stdClass $progress = null
    ): array {
        global $DB;

        if ($progress === null) {
            $batch = progress_service::get_progress_batch($activity, $context, [$userid]);
            $progress = $batch[$userid] ?? null;
        }
        $percent = $progress ? (int)$progress->percent : 0;
        $override = $DB->get_record('vtrackpremium_override', [
            'activityid' => $activity->id,
            'userid' => $userid,
        ]);
        $state = $DB->get_record('vtrackpremium_state', [
            'activityid' => $activity->id,
            'userid' => $userid,
        ]);
        $deadline = self::effective_deadline($activity, $override);
        $extended = $override && !empty($override->deadline) && (int)$override->deadline !== (int)$activity->deadline;
        $completed = $state && !empty($state->completed);

        $status = self::classify(
            $percent,
            (int)$activity->minimumpercent,
            time(),
            (int)$activity->availablefrom,
            $deadline,
            (bool)($override->waived ?? false),
            (bool)$extended,
            (bool)$completed
        );

        $lastreminder = (int)$DB->get_field_sql(
            "SELECT MAX(timesent)
               FROM {vtrackpremium_notify}
              WHERE activityid = :activityid
                AND userid = :userid
                AND status = :status",
            ['activityid' => $activity->id, 'userid' => $userid, 'status' => 'sent']
        );

        $completiontime = $state ? (int)$state->completiontime : 0;
        $compliance = 'notcompleted';
        if ($override && !empty($override->waived)) {
            $compliance = 'waived';
        } else if ($completiontime > 0) {
            $compliance = ($deadline > 0 && $completiontime > $deadline) ? 'completedlate' : 'completedontime';
        }

        return [
            'userid' => $userid,
            'percent' => $percent,
            'status' => $status,
            'deadline' => $deadline,
            'extended' => (bool)$extended,
            'waived' => (bool)($override->waived ?? false),
            'lastsession' => $progress ? (int)$progress->timemodified : 0,
            'lastreminder' => $lastreminder,
            'completiontime' => $completiontime,
            'compliance' => $compliance,
        ];
    }
}
