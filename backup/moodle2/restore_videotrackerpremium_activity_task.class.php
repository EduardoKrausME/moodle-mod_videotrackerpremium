<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videotrackerpremium/backup/moodle2/restore_videotrackerpremium_stepslib.php');

/**
 * Video Tracker Premium restore task.
 */
class restore_videotrackerpremium_activity_task extends restore_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(
            new restore_videotrackerpremium_activity_structure_step(
                'videotrackerpremium_structure',
                'videotrackerpremium.xml'
            )
        );
    }

    public static function define_decode_contents(): array {
        return [
            new restore_decode_content(
                'videotrackerpremium',
                ['intro'],
                'videotrackerpremium'
            ),
        ];
    }

    public static function define_decode_rules(): array {
        return [
            new restore_decode_rule(
                'VIDEOTRACKERPREMIUMVIEWBYID',
                '/mod/videotrackerpremium/view.php?id=$1',
                'course_module'
            ),
            new restore_decode_rule(
                'VIDEOTRACKERPREMIUMINDEX',
                '/mod/videotrackerpremium/index.php?id=$1',
                'course'
            ),
        ];
    }

    public static function define_restore_log_rules(): array {
        return [];
    }

    public static function define_restore_log_rules_for_course(): array {
        return [];
    }
}
