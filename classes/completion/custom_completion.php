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
 * custom_completion.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\completion;

use coding_exception;
use core_completion\activity_custom_completion;
use context_module;
use mod_videotrackerpremium\local\service\progress_service;

/**
 * Custom completion based only on authoritative Video Bridge progress or a waiver.
 */
class custom_completion extends activity_custom_completion {
    /**
     * Method get_state.
     *
     * @param string $rule Parameter rule.
     * @return int Return value.
     */
    public function get_state(string $rule): int {
        global $DB;

        if (!$this->is_defined($rule)) {
            throw new coding_exception("Undefined custom completion rule '{$rule}'");
        }

        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => $this->cm->instance],
            '*',
            MUST_EXIST
        );
        if (empty($activity->completionrequired)) {
            return COMPLETION_COMPLETE;
        }

        $override = $DB->get_record('videotrackerpremium_override', [
            'activityid' => $activity->id,
            'userid' => $this->userid,
        ]);
        if ($override && !empty($override->waived)) {
            return COMPLETION_COMPLETE;
        }

        $context = context_module::instance($this->cm->id);
        $progresses = progress_service::get_progress_batch(
            $activity,
            $context,
            [(int)$this->userid]
        );
        $percent = isset($progresses[$this->userid])
            ? (int)$progresses[$this->userid]->percent
            : 0;

        return $percent >= (int)$activity->minimumpercent
            ? COMPLETION_COMPLETE
            : COMPLETION_INCOMPLETE;
    }

    /**
     * Method get_defined_custom_rules.
     *
     * @return array Return value.
     */
    public static function get_defined_custom_rules(): array {
        return ['completionrequired'];
    }

    /**
     * Method get_custom_rule_descriptions.
     *
     * @return array Return value.
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;

        $percent = (int)$DB->get_field(
            'videotrackerpremium',
            'minimumpercent',
            ['id' => $this->cm->instance],
            MUST_EXIST
        );
        return [
            'completionrequired' => get_string(
                'completiondetail',
                'videotrackerpremium',
                $percent
            ),
        ];
    }

    /**
     * Method get_sort_order.
     *
     * @return array Return value.
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionrequired',
        ];
    }
}
