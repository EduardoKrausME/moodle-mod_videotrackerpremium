<?php
namespace mod_videotrackerpremium\local\service;

use context_module;
use mod_videotrackerpremium\event\deadline_extended;
use mod_videotrackerpremium\event\user_waived;
use mod_videotrackerpremium\event\waiver_removed;
use stdClass;

/**
 * Applies audited individual operational exceptions.
 */
class override_service {
    private static function get_or_create(int $activityid, int $userid): stdClass {
        global $DB;

        $record = $DB->get_record('videotrackerpremium_override', ['activityid' => $activityid, 'userid' => $userid]);
        if ($record) {
            return $record;
        }
        $now = time();
        $record = (object)[
            'activityid' => $activityid,
            'userid' => $userid,
            'deadline' => 0,
            'waived' => 0,
            'adminnote' => '',
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('videotrackerpremium_override', $record);
        return $record;
    }

    private static function history(
        int $activityid,
        int $userid,
        int $actorid,
        string $action,
        int $olddeadline,
        int $newdeadline,
        string $details = ''
    ): void {
        global $DB;

        $DB->insert_record('videotrackerpremium_history', (object)[
            'activityid' => $activityid,
            'userid' => $userid,
            'actorid' => $actorid,
            'action' => $action,
            'olddeadline' => $olddeadline,
            'newdeadline' => $newdeadline,
            'details' => $details,
            'timecreated' => time(),
        ]);
    }

    public static function set_deadline(
        stdClass $activity,
        context_module $context,
        int $userid,
        int $deadline,
        int $actorid,
        string $note = ''
    ): void {
        global $DB;

        recipient_guard::require_authorised($context, $userid, $actorid);
        $override = self::get_or_create((int)$activity->id, $userid);
        $olddeadline = status_service::effective_deadline($activity, $override);
        $override->deadline = max(0, $deadline);
        if ($note !== '') {
            $override->adminnote = clean_param($note, PARAM_TEXT);
        }
        $override->timemodified = time();
        $DB->update_record('videotrackerpremium_override', $override);

        $action = $deadline > $olddeadline ? 'deadline_extended' : 'deadline_changed';
        self::history((int)$activity->id, $userid, $actorid, $action, $olddeadline, $deadline, $note);

        if ($deadline > $olddeadline) {
            $event = deadline_extended::create([
                'objectid' => $override->id,
                'context' => $context,
                'relateduserid' => $userid,
                'other' => [
                    'activityid' => (int)$activity->id,
                    'olddeadline' => $olddeadline,
                    'newdeadline' => $deadline,
                ],
            ]);
            $event->trigger();
        }
        reminder_service::cancel_user_pending((int)$activity->id, $userid, 'deadlinechanged');
        reminder_service::synchronise_user($activity, $context, $userid);
    }

    public static function set_waiver(
        stdClass $activity,
        context_module $context,
        int $userid,
        bool $waived,
        int $actorid,
        string $note = ''
    ): void {
        global $DB;

        recipient_guard::require_authorised($context, $userid, $actorid);
        $override = self::get_or_create((int)$activity->id, $userid);
        $previous = !empty($override->waived);
        $override->waived = $waived ? 1 : 0;
        if ($note !== '') {
            $override->adminnote = clean_param($note, PARAM_TEXT);
        }
        $override->timemodified = time();
        $DB->update_record('videotrackerpremium_override', $override);

        self::history(
            (int)$activity->id,
            $userid,
            $actorid,
            $waived ? 'waived' : 'waiver_removed',
            0,
            0,
            $note
        );

        if ($previous !== $waived) {
            $class = $waived ? user_waived::class : waiver_removed::class;
            $event = $class::create([
                'objectid' => $override->id,
                'context' => $context,
                'relateduserid' => $userid,
                'other' => ['activityid' => (int)$activity->id],
            ]);
            $event->trigger();
        }

        reminder_service::cancel_user_pending((int)$activity->id, $userid, $waived ? 'waived' : 'waiverremoved');
        if (!$waived) {
            reminder_service::synchronise_user($activity, $context, $userid);
        }

        if (!empty($activity->completionrequired)) {
            $cm = get_coursemodule_from_instance(
                'videotrackerpremium',
                $activity->id,
                $activity->course,
                false,
                MUST_EXIST
            );
            $course = get_course($activity->course);
            (new \completion_info($course))->update_state(
                $cm,
                COMPLETION_UNKNOWN,
                $userid,
                true
            );
        }
    }

    public static function set_note(
        stdClass $activity,
        context_module $context,
        int $userid,
        string $note,
        int $actorid
    ): void {
        global $DB;

        recipient_guard::require_authorised($context, $userid, $actorid);
        $override = self::get_or_create((int)$activity->id, $userid);
        $override->adminnote = clean_param($note, PARAM_TEXT);
        $override->timemodified = time();
        $DB->update_record('videotrackerpremium_override', $override);
        self::history((int)$activity->id, $userid, $actorid, 'note_updated', 0, 0, $override->adminnote);
    }
}
