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
 * restore_videotrackerpremium_stepslib.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Restores activity configuration and optional user operational data.
 */
class restore_videotrackerpremium_activity_structure_step extends restore_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return array Return value.
     */
    protected function define_structure(): array {
        $paths = [
            new restore_path_element(
                'videotrackerpremium',
                '/activity/videotrackerpremium'
            ),
        ];

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'videotrackerpremium_override',
                '/activity/videotrackerpremium/overrides/override'
            );
            $paths[] = new restore_path_element(
                'videotrackerpremium_history',
                '/activity/videotrackerpremium/historyitems/history'
            );
            $paths[] = new restore_path_element(
                'videotrackerpremium_notify',
                '/activity/videotrackerpremium/notifications/notification'
            );
            $paths[] = new restore_path_element(
                'videotrackerpremium_state',
                '/activity/videotrackerpremium/states/state'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Method process_videotrackerpremium.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
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

    /**
     * Method process_videotrackerpremium_override.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videotrackerpremium_override($data): void {
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
        $DB->insert_record('videotrackerpremium_override', $data);
    }

    /**
     * Method process_videotrackerpremium_history.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videotrackerpremium_history($data): void {
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
        $DB->insert_record('videotrackerpremium_history', $data);
    }

    /**
     * Method process_videotrackerpremium_notify.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videotrackerpremium_notify($data): void {
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
        $DB->insert_record('videotrackerpremium_notify', $data);
    }

    /**
     * Method process_videotrackerpremium_state.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_videotrackerpremium_state($data): void {
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
        $DB->insert_record('videotrackerpremium_state', $data);
    }

    /**
     * Method after_execute.
     *
     * @return void Return value.
     */
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
