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
 * bulk_action_batch.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\task;

use context_module;
use core\task\adhoc_task;
use mod_videotrackerpremium\local\service\bulk_service;

/**
 * Applies a large validated operational action outside the request cycle.
 */
class bulk_action_batch extends adhoc_task {
    /**
     * Method execute.
     *
     * @return void Return value.
     */
    public function execute(): void {
        global $DB;

        $data = $this->get_custom_data();
        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => (int)$data->activityid],
            '*',
            IGNORE_MISSING
        );
        if (!$activity) {
            return;
        }
        $cm = get_coursemodule_from_instance(
            'videotrackerpremium',
            $activity->id,
            $activity->course,
            false,
            IGNORE_MISSING
        );
        if (!$cm) {
            return;
        }

        bulk_service::execute(
            $activity,
            context_module::instance($cm->id),
            array_map('intval', (array)$data->userids),
            clean_param((string)$data->action, PARAM_ALPHA),
            (int)$data->value,
            (int)$data->actorid
        );
    }
}
