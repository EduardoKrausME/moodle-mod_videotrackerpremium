<?php
namespace mod_videotrackerpremium\event;

defined('MOODLE_INTERNAL') || die;

/**
 * Reminder sent event.
 */
class reminder_sent extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'vtrackpremium_notify';
    }

    public static function get_name(): string {
        return get_string('reminder_sent', 'videotrackerpremium');
    }

    public function get_description(): string {
        return "User '{$this->userid}' sent reminder '{$this->other['type']}' to user '{$this->relateduserid}'.";
    }
}
