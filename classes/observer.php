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
 * observer.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium;

use context_module;
use mod_videotrackerpremium\local\service\progress_service;

/**
 * Observes public Video Bridge progress events.
 */
class observer {
    /**
     * Method progress_threshold_reached.
     *
     * @param \local_video_bridge\event\progress_threshold_reached $event Parameter event.
     * @return void Return value.
     */
    public static function progress_threshold_reached(
        \local_video_bridge\event\progress_threshold_reached $event
    ): void {
        global $DB;

        $other = $event->other;
        if (($other['component'] ?? '') !== 'mod_videotrackerpremium') {
            return;
        }

        $activityid = (int)($other['itemid'] ?? 0);
        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => $activityid],
            '*',
            IGNORE_MISSING
        );
        if (!$activity || (int)($other['threshold'] ?? 0) < (int)$activity->minimumpercent) {
            return;
        }

        $context = context_module::instance_by_id($event->contextid, IGNORE_MISSING);
        if (!$context || empty($event->relateduserid)) {
            return;
        }
        progress_service::sync_completion(
            $activity,
            $context,
            (int)$event->relateduserid
        );
    }

    /**
     * Method video_completed.
     *
     * @param \local_video_bridge\event\video_completed $event Parameter event.
     * @return void Return value.
     */
    public static function video_completed(
        \local_video_bridge\event\video_completed $event
    ): void {
        global $DB;

        $other = $event->other;
        if (($other['component'] ?? '') !== 'mod_videotrackerpremium') {
            return;
        }

        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => (int)($other['itemid'] ?? 0)],
            '*',
            IGNORE_MISSING
        );
        if (!$activity || empty($event->relateduserid)) {
            return;
        }

        $context = context_module::instance_by_id($event->contextid, IGNORE_MISSING);
        if ($context) {
            progress_service::sync_completion(
                $activity,
                $context,
                (int)$event->relateduserid
            );
        }
    }
}
