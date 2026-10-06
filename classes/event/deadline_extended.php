<?php
namespace mod_videotrackerpremium\event;

defined('MOODLE_INTERNAL') || die;

/**
 * Individual deadline extended event.
 */
class deadline_extended extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'vtrackpremium_override';
    }

    public static function get_name(): string {
        return get_string('deadline_extended', 'videotrackerpremium');
    }

    public function get_description(): string {
        return "The deadline for user '{$this->relateduserid}' was extended from " .
            "'{$this->other['olddeadline']}' to '{$this->other['newdeadline']}'.";
    }
}
