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
 * recipient_guard.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\local\service;

use context_module;

/**
 * Validates that operational actions only target authorised enrolled users.
 */
class recipient_guard {
    /**
     * Returns active enrolled users who are tracked by Moodle completion reports.
     *
     * @param context_module $context Activity context.
     * @param string $fields User fields requested from the enrolment API.
     * @return array Users indexed by id.
     */
    public static function get_eligible_users(context_module $context, string $fields = 'u.id'): array {
        $cm = get_coursemodule_from_id(
            'videotrackerpremium',
            $context->instanceid,
            0,
            false,
            MUST_EXIST
        );
        $course = get_course($cm->course);
        $completion = new \completion_info($course);
        $tracked = $completion->get_tracked_users();
        if (!$tracked) {
            return [];
        }

        $enrolled = get_enrolled_users(
            $context,
            'mod/videotrackerpremium:view',
            0,
            $fields,
            null,
            0,
            0,
            true
        );

        return array_intersect_key(
            $enrolled,
            array_fill_keys(array_map('intval', array_keys($tracked)), true)
        );
    }

    /**
     * Method filter_authorised.
     *
     * @param context_module $context Parameter context.
     * @param array $requested Parameter requested.
     * @param ?int $actorid Parameter actorid.
     * @return array Return value.
     */
    public static function filter_authorised(context_module $context, array $requested, ?int $actorid = null): array {
        global $USER;

        $actorid ??= (int)$USER->id;
        $requested = array_values(array_unique(array_filter(array_map('intval', $requested))));
        if (!$requested) {
            return [];
        }

        $enrolled = self::get_eligible_users($context, 'u.id');
        $allowed = array_fill_keys(array_map('intval', array_keys($enrolled)), true);

        $cm = get_coursemodule_from_id('videotrackerpremium', $context->instanceid, 0, false, MUST_EXIST);
        if (groups_get_activity_groupmode($cm) !== NOGROUPS &&
                !has_capability('moodle/site:accessallgroups', $context, $actorid)) {
            $groups = groups_get_all_groups($cm->course, $actorid, $cm->groupingid ?: 0);
            $groupusers = [];
            foreach ($groups as $group) {
                foreach (groups_get_members($group->id, 'u.id') as $member) {
                    $groupusers[(int)$member->id] = true;
                }
            }
            $allowed = array_intersect_key($allowed, $groupusers);
        }

        return array_values(array_filter($requested, static fn(int $id): bool => isset($allowed[$id])));
    }

    /**
     * Method require_authorised.
     *
     * @param context_module $context Parameter context.
     * @param int $userid Parameter userid.
     * @param ?int $actorid Parameter actorid.
     * @return void Return value.
     */
    public static function require_authorised(context_module $context, int $userid, ?int $actorid = null): void {
        if (self::filter_authorised($context, [$userid], $actorid) !== [$userid]) {
            throw new \required_capability_exception(
                $context,
                'mod/videotrackerpremium:manageoverrides',
                'nopermissions',
                ''
            );
        }
    }
}
