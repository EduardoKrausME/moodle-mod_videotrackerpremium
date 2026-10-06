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
 * status_service_test.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\local\service;

defined('MOODLE_INTERNAL') || die;

/**
 * Tests operational status classification.
 */
final class status_service_test extends \advanced_testcase {
    /**
     * Method test_status_precedence.
     *
     * @return void Return value.
     */
    public function test_status_precedence(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $USER->timezone = 'UTC';

        $now = make_timestamp(2026, 10, 6, 12, 0, 0, 'UTC');

        $this->assertSame(
            'waived',
            status_service::classify(100, 90, $now, 0, $now - HOURSECS, true, false, true)
        );
        $this->assertSame('completed', status_service::classify(90, 90, $now, 0, $now - HOURSECS));
        $this->assertSame('notavailable', status_service::classify(0, 90, $now, $now + HOURSECS, $now + WEEKSECS));
        $this->assertSame('overdue', status_service::classify(50, 90, $now, 0, $now - 1));
        $this->assertSame(
            'duetoday',
            status_service::classify(
                50,
                90,
                $now,
                0,
                make_timestamp(2026, 10, 6, 23, 0, 0, 'UTC')
            )
        );
        $this->assertSame('duesoon', status_service::classify(50, 90, $now, 0, $now + (2 * DAYSECS)));
        $this->assertSame('extended', status_service::classify(50, 90, $now, 0, $now + WEEKSECS, false, true));
        $this->assertSame('inprogress', status_service::classify(1, 90, $now, 0, $now + WEEKSECS));
        $this->assertSame('notstarted', status_service::classify(0, 90, $now, 0, $now + WEEKSECS));
    }
}
