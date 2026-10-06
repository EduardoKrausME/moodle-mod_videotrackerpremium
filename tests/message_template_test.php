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
 * message_template_test.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\local\service;

defined('MOODLE_INTERNAL') || die;

/**
 * Tests the deliberately small and non-evaluating message template language.
 */
final class message_template_test extends \advanced_testcase {
    /**
     * Method test_render_replaces_only_known_placeholders.
     *
     * @return void Return value.
     */
    public function test_render_replaces_only_known_placeholders(): void {
        $template = 'Hi {firstname}: {activityname} / {deadline} / {percent} / {course} / {unknown} / {{7*7}}';

        $rendered = message_template::render($template, [
            'firstname' => 'Ada',
            'activityname' => 'Safety',
            'deadline' => '10 October',
            'percent' => 82,
            'course' => 'Onboarding',
            'unknown' => 'must-not-be-used',
        ]);

        $this->assertSame(
            'Hi Ada: Safety / 10 October / 82 / Onboarding / {unknown} / {{7*7}}',
            $rendered
        );
    }

    /**
     * Method test_render_does_not_evaluate_content.
     *
     * @return void Return value.
     */
    public function test_render_does_not_evaluate_content(): void {
        $template = '{firstname} <?php echo 7 * 7; ?> ${7*7}';
        $this->assertSame(
            'Grace <?php echo 7 * 7; ?> ${7*7}',
            message_template::render($template, ['firstname' => 'Grace'])
        );
    }
}
