<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * status_service.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\local\service;

use context_module;
use stdClass;

/**
 * Operational status and compliance classification.
 */
class status_service {
    /**
     * Method effective_deadline.
     *
     * @param stdClass $activity Parameter activity.
     * @param ?stdClass $override Parameter override.
     * @return int Return value.
     */
    public static function effective_deadline(stdClass $activity, ?stdClass $override): int {
        if ($override && !empty($override->deadline)) {
            return (int)$override->deadline;
        }
        return (int)$activity->deadline;
    }

    /**
     * Method classify.
     *
     * @param int $percent Parameter percent.
     * @param int $required Parameter required.
     * @param int $now Parameter now.
     * @param int $availablefrom Parameter availablefrom.
     * @param int $deadline Parameter deadline.
     * @param bool $waived Parameter waived.
     * @param bool $extended Parameter extended.
     * @param bool $completed Parameter completed.
     * @return string Return value.
     */
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

    /**
     * Method get_user_status.
     *
     * @param stdClass $activity Parameter activity.
     * @param context_module $context Parameter context.
     * @param int $userid Parameter userid.
     * @param ?stdClass $progress Parameter progress.
     * @return array Return value.
     */
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
        $override = $DB->get_record('videotrackerpremium_override', [
            'activityid' => $activity->id,
            'userid' => $userid,
        ]);
        $state = $DB->get_record('videotrackerpremium_state', [
            'activityid' => $activity->id,
            'userid' => $userid,
        ]);
        $deadline = self::effective_deadline($activity, $override);
        $originaldeadline = (int)$activity->deadline;
        $extended = $override && !empty($override->deadline) &&
            ($originaldeadline <= 0 || (int)$override->deadline > $originaldeadline);
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
               FROM {videotrackerpremium_notify}
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
            'lastaccess' => $state ? (int)($state->lastaccess ?? 0) : 0,
            'lastsession' => $progress ? (int)($progress->lastsession ?? 0) : 0,
            'lastreminder' => $lastreminder,
            'completiontime' => $completiontime,
            'compliance' => $compliance,
        ];
    }
}
