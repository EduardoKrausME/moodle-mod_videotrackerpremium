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
 * bulk_service.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\local\service;

use context_module;
use stdClass;

/**
 * Shared validated implementation for bulk operations.
 */
class bulk_service {
    /**
     * Method execute.
     *
     * @param stdClass $activity Parameter activity.
     * @param context_module $context Parameter context.
     * @param array $requestedids Parameter requestedids.
     * @param string $action Parameter action.
     * @param int $value Parameter value.
     * @param int $actorid Parameter actorid.
     * @return array Return value.
     */
    public static function execute(
        stdClass $activity,
        context_module $context,
        array $requestedids,
        string $action,
        int $value,
        int $actorid
    ): array {
        $userids = recipient_guard::filter_authorised($context, $requestedids, $actorid);
        if (!$userids) {
            return [];
        }

        foreach ($userids as $userid) {
            switch ($action) {
                case 'remind':
                    require_capability('mod/videotrackerpremium:sendreminders', $context, $actorid);
                    reminder_service::queue_manual((int)$activity->id, $userid);
                    break;
                case 'extend':
                    require_capability('mod/videotrackerpremium:manageoverrides', $context, $actorid);
                    $status = status_service::get_user_status($activity, $context, $userid);
                    $base = $status['deadline'] ?: time();
                    override_service::set_deadline(
                        $activity,
                        $context,
                        $userid,
                        $base + (max(1, $value) * DAYSECS),
                        $actorid
                    );
                    break;
                case 'waive':
                    require_capability('mod/videotrackerpremium:manageoverrides', $context, $actorid);
                    override_service::set_waiver($activity, $context, $userid, true, $actorid);
                    break;
                case 'unwaive':
                    require_capability('mod/videotrackerpremium:manageoverrides', $context, $actorid);
                    override_service::set_waiver($activity, $context, $userid, false, $actorid);
                    break;
                default:
                    throw new \invalid_parameter_exception('Invalid bulk action.');
            }
        }
        return $userids;
    }
}
