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
 * provider.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for operational compliance data.
 *
 * Administrative history is retained when the requester was only the actor:
 * the actor identifier is anonymised to 0 rather than deleting another
 * learner's audit row. Rows where the requester is the affected learner are
 * personal data and are deleted.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    plugin_provider,
    core_userlist_provider {

    /**
     * Method get_metadata.
     *
     * @param collection $collection Parameter collection.
     * @return collection Return value.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videotrackerpremium_override', [
            'userid' => 'privacy:metadata:override',
            'deadline' => 'privacy:metadata:override',
            'waived' => 'privacy:metadata:override',
            'adminnote' => 'privacy:metadata:override',
            'timecreated' => 'privacy:metadata:override',
            'timemodified' => 'privacy:metadata:override',
        ], 'privacy:metadata:override');

        $collection->add_database_table('videotrackerpremium_history', [
            'userid' => 'privacy:metadata:history',
            'actorid' => 'privacy:metadata:history',
            'action' => 'privacy:metadata:history',
            'olddeadline' => 'privacy:metadata:history',
            'newdeadline' => 'privacy:metadata:history',
            'details' => 'privacy:metadata:history',
            'timecreated' => 'privacy:metadata:history',
        ], 'privacy:metadata:history');

        $collection->add_database_table('videotrackerpremium_notify', [
            'userid' => 'privacy:metadata:notify',
            'type' => 'privacy:metadata:notify',
            'scheduledfor' => 'privacy:metadata:notify',
            'timesent' => 'privacy:metadata:notify',
            'status' => 'privacy:metadata:notify',
        ], 'privacy:metadata:notify');

        $collection->add_database_table('videotrackerpremium_state', [
            'userid' => 'privacy:metadata:state',
            'completed' => 'privacy:metadata:state',
            'completiontime' => 'privacy:metadata:state',
            'completionpercent' => 'privacy:metadata:state',
            'laststatus' => 'privacy:metadata:state',
            'lastaccess' => 'privacy:metadata:state',
        ], 'privacy:metadata:state');

        return $collection;
    }

    /**
     * Method get_contexts_for_userid.
     *
     * @param int $userid Parameter userid.
     * @return contextlist Return value.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm
                    ON cm.id = ctx.instanceid
                  JOIN {modules} m
                    ON m.id = cm.module
                   AND m.name = :modname
                  JOIN {videotrackerpremium} v
                    ON v.id = cm.instance
                 WHERE ctx.contextlevel = :contextlevel
                   AND (
                       EXISTS (
                           SELECT 1
                             FROM {videotrackerpremium_override} o
                            WHERE o.activityid = v.id
                              AND o.userid = :overrideuserid
                       )
                       OR EXISTS (
                           SELECT 1
                             FROM {videotrackerpremium_history} h
                            WHERE h.activityid = v.id
                              AND (h.userid = :historyuserid OR h.actorid = :actoruserid)
                       )
                       OR EXISTS (
                           SELECT 1
                             FROM {videotrackerpremium_notify} n
                            WHERE n.activityid = v.id
                              AND n.userid = :notifyuserid
                       )
                       OR EXISTS (
                           SELECT 1
                             FROM {videotrackerpremium_state} s
                            WHERE s.activityid = v.id
                              AND s.userid = :stateuserid
                       )
                   )";
        $contextlist->add_from_sql($sql, [
            'modname' => 'videotrackerpremium',
            'contextlevel' => CONTEXT_MODULE,
            'overrideuserid' => $userid,
            'historyuserid' => $userid,
            'actoruserid' => $userid,
            'notifyuserid' => $userid,
            'stateuserid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Method get_users_in_context.
     *
     * @param userlist $userlist Parameter userlist.
     * @return mixed Return value.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }
        $activityid = self::activity_id($context);
        if (!$activityid) {
            return;
        }

        $sql = "SELECT userid FROM {videotrackerpremium_override} WHERE activityid = :a1
                UNION
                SELECT userid FROM {videotrackerpremium_history} WHERE activityid = :a2
                UNION
                SELECT actorid AS userid
                  FROM {videotrackerpremium_history}
                 WHERE activityid = :a3 AND actorid > 0
                UNION
                SELECT userid FROM {videotrackerpremium_notify} WHERE activityid = :a4
                UNION
                SELECT userid FROM {videotrackerpremium_state} WHERE activityid = :a5";
        $userlist->add_from_sql('userid', $sql, [
            'a1' => $activityid,
            'a2' => $activityid,
            'a3' => $activityid,
            'a4' => $activityid,
            'a5' => $activityid,
        ]);
    }

    /**
     * Method export_user_data.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return mixed Return value.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }
        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            $activityid = self::activity_id($context);
            if (!$activityid) {
                continue;
            }

            $override = $DB->get_record('videotrackerpremium_override', [
                'activityid' => $activityid,
                'userid' => $userid,
            ]);
            if ($override) {
                writer::with_context($context)->export_data(
                    [get_string('override', 'videotrackerpremium')],
                    (object)[
                        'deadline' => $override->deadline
                            ? transform::datetime($override->deadline)
                            : null,
                        'waived' => (bool)$override->waived,
                        'adminnote' => $override->adminnote,
                        'timecreated' => transform::datetime($override->timecreated),
                        'timemodified' => transform::datetime($override->timemodified),
                    ]
                );
            }

            $notifications = [];
            foreach ($DB->get_records('videotrackerpremium_notify', [
                'activityid' => $activityid,
                'userid' => $userid,
            ], 'timecreated ASC') as $record) {
                $notifications[] = (object)[
                    'type' => $record->type,
                    'scheduledfor' => transform::datetime($record->scheduledfor),
                    'timesent' => $record->timesent ? transform::datetime($record->timesent) : null,
                    'status' => $record->status,
                ];
            }
            if ($notifications) {
                writer::with_context($context)->export_data(
                    [get_string('reminderheader', 'videotrackerpremium')],
                    (object)['notifications' => $notifications]
                );
            }

            $state = $DB->get_record('videotrackerpremium_state', [
                'activityid' => $activityid,
                'userid' => $userid,
            ]);
            if ($state) {
                writer::with_context($context)->export_data(
                    [get_string('completion', 'videotrackerpremium')],
                    (object)[
                        'completed' => (bool)$state->completed,
                        'completiontime' => $state->completiontime
                            ? transform::datetime($state->completiontime)
                            : null,
                        'completionpercent' => (int)$state->completionpercent,
                        'laststatus' => $state->laststatus,
                        'lastaccess' => !empty($state->lastaccess)
                            ? transform::datetime($state->lastaccess)
                            : null,
                        'timemodified' => transform::datetime($state->timemodified),
                    ]
                );
            }

            $history = [];
            $records = $DB->get_records_select(
                'videotrackerpremium_history',
                'activityid = :activityid AND (userid = :userid OR actorid = :actorid)',
                [
                    'activityid' => $activityid,
                    'userid' => $userid,
                    'actorid' => $userid,
                ],
                'timecreated ASC'
            );
            foreach ($records as $record) {
                $history[] = (object)[
                    'relation' => (int)$record->userid === $userid ? 'affected_user' : 'administrator',
                    'action' => $record->action,
                    'olddeadline' => $record->olddeadline
                        ? transform::datetime($record->olddeadline)
                        : null,
                    'newdeadline' => $record->newdeadline
                        ? transform::datetime($record->newdeadline)
                        : null,
                    'details' => $record->details,
                    'timecreated' => transform::datetime($record->timecreated),
                ];
            }
            if ($history) {
                writer::with_context($context)->export_data(
                    [get_string('history', 'videotrackerpremium')],
                    (object)['history' => $history]
                );
            }
        }
    }

    /**
     * Method delete_data_for_all_users_in_context.
     *
     * @param context $context Parameter context.
     * @return mixed Return value.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if (!$context instanceof context_module) {
            return;
        }
        $activityid = self::activity_id($context);
        if (!$activityid) {
            return;
        }
        foreach ([
            'videotrackerpremium_override',
            'videotrackerpremium_history',
            'videotrackerpremium_notify',
            'videotrackerpremium_state',
        ] as $table) {
            $DB->delete_records($table, ['activityid' => $activityid]);
        }
    }

    /**
     * Method delete_data_for_user.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return mixed Return value.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }
        $activityids = [];
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_module) {
                $activityid = self::activity_id($context);
                if ($activityid) {
                    $activityids[] = $activityid;
                }
            }
        }
        self::delete_for_users($activityids, [(int)$contextlist->get_user()->id]);
    }

    /**
     * Method delete_data_for_users.
     *
     * @param approved_userlist $userlist Parameter userlist.
     * @return mixed Return value.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof context_module) {
            return;
        }
        $activityid = self::activity_id($context);
        if (!$activityid) {
            return;
        }
        self::delete_for_users([$activityid], array_map('intval', $userlist->get_userids()));
    }

    /**
     * Method delete_for_users.
     *
     * @param array $activityids Parameter activityids.
     * @param array $userids Parameter userids.
     * @return void Return value.
     */
    private static function delete_for_users(array $activityids, array $userids): void {
        global $DB;

        $activityids = array_values(array_unique(array_filter(array_map('intval', $activityids))));
        $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
        if (!$activityids || !$userids) {
            return;
        }

        [$activitysql, $activityparams] = $DB->get_in_or_equal(
            $activityids,
            SQL_PARAMS_NAMED,
            'activity'
        );
        [$usersql, $userparams] = $DB->get_in_or_equal(
            $userids,
            SQL_PARAMS_NAMED,
            'privacyuser'
        );
        $params = $activityparams + $userparams;

        foreach ([
            'videotrackerpremium_override',
            'videotrackerpremium_notify',
            'videotrackerpremium_state',
        ] as $table) {
            $DB->delete_records_select(
                $table,
                "activityid {$activitysql} AND userid {$usersql}",
                $params
            );
        }

        // Rows about the user are personal data and can be removed.
        $DB->delete_records_select(
            'videotrackerpremium_history',
            "activityid {$activitysql} AND userid {$usersql}",
            $params
        );

        // Rows about somebody else remain an administrative audit record,
        // but the deleted administrator must no longer be identifiable.
        $DB->set_field_select(
            'videotrackerpremium_history',
            'actorid',
            0,
            "activityid {$activitysql} AND actorid {$usersql}",
            $params
        );
    }

    /**
     * Method activity_id.
     *
     * @param context_module $context Parameter context.
     * @return int Return value.
     */
    private static function activity_id(context_module $context): int {
        $cm = get_coursemodule_from_id(
            'videotrackerpremium',
            $context->instanceid,
            0,
            false,
            IGNORE_MISSING
        );
        return $cm ? (int)$cm->instance : 0;
    }
}
