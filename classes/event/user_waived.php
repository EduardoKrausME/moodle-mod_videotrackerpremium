<?php
namespace mod_videotrackerpremium\event;

defined('MOODLE_INTERNAL') || die;

/**
 * Learner waived event.
 */
class user_waived extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'vtrackpremium_override';
    }

    public static function get_name(): string {
        return get_string('user_waived', 'videotrackerpremium');
    }

    public function get_description(): string {
        return "User '{$this->relateduserid}' was waived from the video requirement.";
    }
}
