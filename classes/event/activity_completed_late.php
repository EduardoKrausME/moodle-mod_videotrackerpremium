<?php
namespace mod_videotrackerpremium\event;

defined('MOODLE_INTERNAL') || die;

/**
 * Activity completed after effective deadline event.
 */
class activity_completed_late extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'vtrackpremium_state';
    }

    public static function get_name(): string {
        return get_string('activity_completed_late', 'videotrackerpremium');
    }

    public function get_description(): string {
        return "User '{$this->relateduserid}' completed the activity after its effective deadline.";
    }
}
