<?php
namespace mod_videotrackerpremium\event;

defined('MOODLE_INTERNAL') || die;

/**
 * Waiver removed event.
 */
class waiver_removed extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'vtrackpremium_override';
    }

    public static function get_name(): string {
        return get_string('waiver_removed', 'videotrackerpremium');
    }

    public function get_description(): string {
        return "The waiver for user '{$this->relateduserid}' was removed.";
    }
}
