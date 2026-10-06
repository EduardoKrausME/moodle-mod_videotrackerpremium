<?php
defined('MOODLE_INTERNAL') || die;

/**
 * Restores activity configuration and optional user operational data.
 */
class restore_videotrackerpremium_activity_structure_step extends restore_activity_structure_step {
    protected function define_structure(): array {
        $paths = [
            new restore_path_element(
                'videotrackerpremium',
                '/activity/videotrackerpremium'
            ),
        ];

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'vtrackpremium_override',
                '/activity/videotrackerpremium/overrides/override'
            );
            $paths[] = new restore_path_element(
                'vtrackpremium_history',
                '/activity/videotrackerpremium/historyitems/history'
            );
            $paths[] = new restore_path_element(
                'vtrackpremium_notify',
                '/activity/videotrackerpremium/notifications/notification'
            );
            $paths[] = new restore_path_element(
                'vtrackpremium_state',
                '/activity/videotrackerpremium/states/state'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_videotrackerpremium($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        if (!empty($data->availablefrom)) {
            $data->availablefrom = $this->apply_date_offset($data->availablefrom);
        }
        if (!empty($data->deadline)) {
            $data->deadline = $this->apply_date_offset($data->deadline);
        }

        $newid = $DB->insert_record('videotrackerpremium', $data);
        $this->apply_activity_instance($newid);
        $this->set_mapping('videotrackerpremium', $oldid, $newid, true);
    }

    protected function process_vtrackpremium_override($data): void {
        global $DB;

        $data = (object)$data;
        $data->activityid = $this->get_new_parentid('videotrackerpremium');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if (!$data->userid) {
            return;
        }
        if (!empty($data->deadline)) {
            $data->deadline = $this->apply_date_offset($data->deadline);
        }
        $DB->insert_record('vtrackpremium_override', $data);
    }

    protected function process_vtrackpremium_history($data): void {
        global $DB;

        $data = (object)$data;
        $data->activityid = $this->get_new_parentid('videotrackerpremium');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        $data->actorid = $data->actorid
            ? $this->get_mappingid('user', $data->actorid, 0)
            : 0;
        if (!$data->userid) {
            return;
        }
        if (!empty($data->olddeadline)) {
            $data->olddeadline = $this->apply_date_offset($data->olddeadline);
        }
        if (!empty($data->newdeadline)) {
            $data->newdeadline = $this->apply_date_offset($data->newdeadline);
        }
        $DB->insert_record('vtrackpremium_history', $data);
    }

    protected function process_vtrackpremium_notify($data): void {
        global $DB;

        $data = (object)$data;
        $data->activityid = $this->get_new_parentid('videotrackerpremium');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if (!$data->userid) {
            return;
        }
        foreach (['scheduledfor', 'timesent', 'timecreated', 'timemodified'] as $field) {
            if (!empty($data->{$field})) {
                $data->{$field} = $this->apply_date_offset($data->{$field});
            }
        }
        $DB->insert_record('vtrackpremium_notify', $data);
    }

    protected function process_vtrackpremium_state($data): void {
        global $DB;

        $data = (object)$data;
        $data->activityid = $this->get_new_parentid('videotrackerpremium');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if (!$data->userid) {
            return;
        }
        foreach (['completiontime', 'lastaccess', 'timemodified'] as $field) {
            if (!empty($data->{$field})) {
                $data->{$field} = $this->apply_date_offset($data->{$field});
            }
        }
        $DB->insert_record('vtrackpremium_state', $data);
    }

    protected function after_execute(): void {
        $this->add_related_files(
            'mod_videotrackerpremium',
            'intro',
            null
        );
        $this->add_related_files(
            'local_video_bridge',
            'video',
            null
        );
    }
}
