<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videotrackerpremium/backup/moodle2/backup_videotrackerpremium_stepslib.php');

/**
 * Video Tracker Premium backup task.
 */
class backup_videotrackerpremium_activity_task extends backup_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(
            new backup_videotrackerpremium_activity_structure_step(
                'videotrackerpremium_structure',
                'videotrackerpremium.xml'
            )
        );
    }

    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot . '/mod/videotrackerpremium/index.php', '/');
        $content = preg_replace(
            "/({$base}\?id=)([0-9]+)/",
            '$@VIDEOTRACKERPREMIUMINDEX*$2@$',
            $content
        );

        $base = preg_quote($CFG->wwwroot . '/mod/videotrackerpremium/view.php', '/');
        return preg_replace(
            "/({$base}\?id=)([0-9]+)/",
            '$@VIDEOTRACKERPREMIUMVIEWBYID*$2@$',
            $content
        );
    }
}
