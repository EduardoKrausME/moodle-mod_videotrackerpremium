<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videotrackerpremium\local\service;

defined('MOODLE_INTERNAL') || die;

/**
 * Tests operational status classification.
 */
final class status_service_test extends \advanced_testcase {
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
