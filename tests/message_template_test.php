<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videotrackerpremium\local\service;

defined('MOODLE_INTERNAL') || die;

/**
 * Tests the deliberately small and non-evaluating message template language.
 */
final class message_template_test extends \advanced_testcase {
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

    public function test_render_does_not_evaluate_content(): void {
        $template = '{firstname} <?php echo 7 * 7; ?> \${7*7}';
        $this->assertSame(
            'Grace <?php echo 7 * 7; ?> ${7*7}',
            message_template::render($template, ['firstname' => 'Grace'])
        );
    }
}
