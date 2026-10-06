<?php
defined('MOODLE_INTERNAL') || die;

/**
 * Defines the activity backup structure.
 */
class backup_videotrackerpremium_activity_structure_step extends backup_activity_structure_step {
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
            ['userid', 'completed', 'completiontime', 'completionpercent', 'laststatus', 'timemodified']
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
                'vtrackpremium_override',
                ['activityid' => backup::VAR_PARENTID]
            );
            $history->set_source_table(
                'vtrackpremium_history',
                ['activityid' => backup::VAR_PARENTID]
            );
            $notification->set_source_table(
                'vtrackpremium_notify',
                ['activityid' => backup::VAR_PARENTID]
            );
            $state->set_source_table(
                'vtrackpremium_state',
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
