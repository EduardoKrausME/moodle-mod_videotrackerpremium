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
 * backup_videotrackerpremium_stepslib.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Defines the activity backup structure.
 */
class backup_videotrackerpremium_activity_structure_step extends backup_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return mixed Return value.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element(
            'videotrackerpremium',
            ['id'],
            [
                'name',
                'intro',
                'introformat',
                'videosource',
                'sourceconfig',
                'videourl',
                'minimumpercent',
                'availablefrom',
                'deadline',
                'graceperiod',
                'allowlate',
                'remindersenabled',
                'reminderconfig',
                'messageavailable',
                'messagenear',
                'messagetomorrow',
                'messageoverdue',
                'messagecompleted',
                'completionrequired',
                'timecreated',
                'timemodified',
            ]
        );

        $overrides = new backup_nested_element('overrides');
        $override = new backup_nested_element(
            'override',
            ['id'],
            ['userid', 'deadline', 'waived', 'adminnote', 'timecreated', 'timemodified']
        );
        $historyitems = new backup_nested_element('historyitems');
        $history = new backup_nested_element(
            'history',
            ['id'],
            ['userid', 'actorid', 'action', 'olddeadline', 'newdeadline', 'details', 'timecreated']
        );
        $notifications = new backup_nested_element('notifications');
        $notification = new backup_nested_element(
            'notification',
            ['id'],
            ['userid', 'type', 'scheduledfor', 'timesent', 'status', 'attempts', 'lasterror', 'timecreated', 'timemodified']
        );
        $states = new backup_nested_element('states');
        $state = new backup_nested_element(
            'state',
            ['id'],
            ['userid', 'completed', 'completiontime', 'completionpercent', 'laststatus', 'lastaccess', 'timemodified']
        );

        $activity->add_child($overrides);
        $overrides->add_child($override);
        $activity->add_child($historyitems);
        $historyitems->add_child($history);
        $activity->add_child($notifications);
        $notifications->add_child($notification);
        $activity->add_child($states);
        $states->add_child($state);

        $activity->set_source_table(
            'videotrackerpremium',
            ['id' => backup::VAR_ACTIVITYID]
        );

        if ($userinfo) {
            $override->set_source_table(
                'videotrackerpremium_override',
                ['activityid' => backup::VAR_PARENTID]
            );
            $history->set_source_table(
                'videotrackerpremium_history',
                ['activityid' => backup::VAR_PARENTID]
            );
            $notification->set_source_table(
                'videotrackerpremium_notify',
                ['activityid' => backup::VAR_PARENTID]
            );
            $state->set_source_table(
                'videotrackerpremium_state',
                ['activityid' => backup::VAR_PARENTID]
            );

            $override->annotate_ids('user', 'userid');
            $history->annotate_ids('user', 'userid');
            $history->annotate_ids('user', 'actorid');
            $notification->annotate_ids('user', 'userid');
            $state->annotate_ids('user', 'userid');
        }

        $activity->annotate_files('mod_videotrackerpremium', 'intro', null);
        $activity->annotate_files('local_video_bridge', 'video', null);

        return $this->prepare_activity_structure($activity);
    }
}
