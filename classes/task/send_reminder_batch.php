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
 * send_reminder_batch.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\task;

use core\task\adhoc_task;
use mod_videotrackerpremium\local\service\reminder_service;

/**
 * Sends a bounded notification batch.
 */
class send_reminder_batch extends adhoc_task {
    /**
     * Method execute.
     *
     * @return void Return value.
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        $ids = array_values(array_unique(array_map('intval', (array)$data->notificationids)));
        foreach (array_slice($ids, 0, 100) as $notificationid) {
            reminder_service::send($notificationid);
        }
    }
}
