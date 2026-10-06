<?php
namespace mod_videotrackerpremium\completion;

use coding_exception;
use core_completion\activity_custom_completion;
use context_module;
use mod_videotrackerpremium\local\service\progress_service;

/**
 * Custom completion based only on authoritative Video Bridge progress or a waiver.
 */
class custom_completion extends activity_custom_completion {
    public function get_state(string $rule): int {
        global $DB;

        if (!$this->is_defined($rule)) {
            throw new coding_exception("Undefined custom completion rule '{$rule}'");
        }

        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => $this->cm->instance],
            '*',
            MUST_EXIST
        );
        if (empty($activity->completionrequired)) {
            return COMPLETION_COMPLETE;
        }

        $override = $DB->get_record('videotrackerpremium_override', [
            'activityid' => $activity->id,
            'userid' => $this->userid,
        ]);
        if ($override && !empty($override->waived)) {
            return COMPLETION_COMPLETE;
        }

        $context = context_module::instance($this->cm->id);
        $progresses = progress_service::get_progress_batch(
            $activity,
            $context,
            [(int)$this->userid]
        );
        $percent = isset($progresses[$this->userid])
            ? (int)$progresses[$this->userid]->percent
            : 0;

        return $percent >= (int)$activity->minimumpercent
            ? COMPLETION_COMPLETE
            : COMPLETION_INCOMPLETE;
    }

    public static function get_defined_custom_rules(): array {
        return ['completionrequired'];
    }

    public function get_custom_rule_descriptions(): array {
        global $DB;

        $percent = (int)$DB->get_field(
            'videotrackerpremium',
            'minimumpercent',
            ['id' => $this->cm->instance],
            MUST_EXIST
        );
        return [
            'completionrequired' => get_string(
                'completiondetail',
                'videotrackerpremium',
                $percent
            ),
        ];
    }

    public function get_sort_order(): array {
        return [
            'completionview',
            'completionrequired',
        ];
    }
}
