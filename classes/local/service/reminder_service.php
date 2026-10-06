<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * reminder_service.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\local\service;

use context_module;
use core\message\message;
use core_user;
use mod_videotrackerpremium\event\reminder_sent;
use stdClass;

/**
 * Notification scheduling and delivery.
 */
class reminder_service {
    /**
     * Method config.
     *
     * @param stdClass $activity Parameter activity.
     * @return array Return value.
     */
    public static function config(stdClass $activity): array {
        $config = json_decode((string)$activity->reminderconfig, true);
        return is_array($config) ? $config : ['sendavailable' => false, 'offsets' => [], 'repeatlateevery' => 0];
    }

    /**
     * Method queue.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @param string $type Parameter type.
     * @param int $scheduledfor Parameter scheduledfor.
     * @return ?int Return value.
     */
    private static function queue(int $activityid, int $userid, string $type, int $scheduledfor): ?int {
        global $DB;

        $params = [
            'activityid' => $activityid,
            'userid' => $userid,
            'type' => $type,
            'scheduledfor' => $scheduledfor,
        ];
        $existing = $DB->get_record('videotrackerpremium_notify', $params);
        if ($existing) {
            if ($existing->status === 'cancelled' && empty($existing->timesent)) {
                $existing->status = 'pending';
                $existing->lasterror = '';
                $existing->timemodified = time();
                $DB->update_record('videotrackerpremium_notify', $existing);
            }
            return (int)$existing->id;
        }

        $now = time();
        return (int)$DB->insert_record('videotrackerpremium_notify', (object)($params + [
            'timesent' => 0,
            'status' => 'pending',
            'attempts' => 0,
            'lasterror' => '',
            'timecreated' => $now,
            'timemodified' => $now,
        ]));
    }

    /**
     * Method cancel_user_pending.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @param string $reason Parameter reason.
     * @return void Return value.
     */
    public static function cancel_user_pending(int $activityid, int $userid, string $reason): void {
        global $DB;

        $records = $DB->get_records_select(
            'videotrackerpremium_notify',
            'activityid = :activityid AND userid = :userid AND status IN (:pending, :queued)',
            [
                'activityid' => $activityid,
                'userid' => $userid,
                'pending' => 'pending',
                'queued' => 'queued',
            ]
        );
        foreach ($records as $record) {
            if (in_array($record->type, ['manual', 'completed'], true)) {
                continue;
            }
            $record->status = 'cancelled';
            $record->lasterror = clean_param($reason, PARAM_TEXT);
            $record->timemodified = time();
            $DB->update_record('videotrackerpremium_notify', $record);
        }
    }

    /**
     * Method cancel_activity_pending.
     *
     * @param int $activityid Parameter activityid.
     * @param string $reason Parameter reason.
     * @param bool $includecompleted Parameter includecompleted.
     * @return void Return value.
     */
    public static function cancel_activity_pending(
        int $activityid,
        string $reason,
        bool $includecompleted = false
    ): void {
        global $DB;

        $records = $DB->get_records_select(
            'videotrackerpremium_notify',
            'activityid = :activityid AND status IN (:pending, :queued)',
            ['activityid' => $activityid, 'pending' => 'pending', 'queued' => 'queued']
        );
        foreach ($records as $record) {
            if ($record->type === 'manual' || (!$includecompleted && $record->type === 'completed')) {
                continue;
            }
            $record->status = 'cancelled';
            $record->lasterror = clean_param($reason, PARAM_TEXT);
            $record->timemodified = time();
            $DB->update_record('videotrackerpremium_notify', $record);
        }
    }

    /**
     * Method synchronise_user.
     *
     * @param stdClass $activity Parameter activity.
     * @param context_module $context Parameter context.
     * @param int $userid Parameter userid.
     * @return void Return value.
     */
    public static function synchronise_user(stdClass $activity, context_module $context, int $userid): void {
        global $DB;

        if (empty($activity->remindersenabled)) {
            self::cancel_user_pending((int)$activity->id, $userid, 'disabled');
            return;
        }
        $state = $DB->get_record('videotrackerpremium_state', ['activityid' => $activity->id, 'userid' => $userid]);
        $override = $DB->get_record('videotrackerpremium_override', ['activityid' => $activity->id, 'userid' => $userid]);
        if (($state && !empty($state->completed)) || ($override && !empty($override->waived))) {
            self::cancel_user_pending((int)$activity->id, $userid, 'notrequired');
            return;
        }

        $config = self::config($activity);
        $now = time();
        $availableat = !empty($activity->availablefrom)
            ? (int)$activity->availablefrom
            : (int)$activity->timecreated;
        if (!empty($config['sendavailable']) && $availableat >= ($now - 600)) {
            self::queue((int)$activity->id, $userid, 'available', $availableat);
        }

        $deadline = status_service::effective_deadline($activity, $override);
        if ($deadline <= 0) {
            return;
        }

        foreach ($config['offsets'] ?? [] as $offset) {
            $offset = (int)$offset;
            $scheduled = $deadline + ($offset * DAYSECS);
            if ($offset < 0 && $scheduled < ($now - 600)) {
                continue;
            }
            if ($offset === 0 && $scheduled < ($now - DAYSECS)) {
                continue;
            }
            $type = match (true) {
                $offset === -1 => 'tomorrow',
                $offset < 0 => 'near',
                $offset === 0 => 'today',
                default => 'overdue',
            };
            self::queue((int)$activity->id, $userid, $type, $scheduled);
        }
    }

    /**
     * Method queue_completion_confirmation.
     *
     * @param stdClass $activity Parameter activity.
     * @param int $userid Parameter userid.
     * @param ?int $when Parameter when.
     * @return void Return value.
     */
    public static function queue_completion_confirmation(stdClass $activity, int $userid, ?int $when = null): void {
        self::queue((int)$activity->id, $userid, 'completed', $when ?? time());
    }

    /**
     * Method queue_manual.
     *
     * @param int $activityid Parameter activityid.
     * @param int $userid Parameter userid.
     * @return int Return value.
     */
    public static function queue_manual(int $activityid, int $userid): int {
        return (int)self::queue($activityid, $userid, 'manual', time());
    }

    /**
     * Method claim_due.
     *
     * @param int $limit Parameter limit.
     * @return array Return value.
     */
    public static function claim_due(int $limit = 200): array {
        global $DB;

        $now = time();
        $records = $DB->get_records_select(
            'videotrackerpremium_notify',
            'scheduledfor <= :now AND (' .
                'status = :pending OR (status = :queued AND timemodified <= :stale)' .
            ')',
            [
                'now' => $now,
                'pending' => 'pending',
                'queued' => 'queued',
                'stale' => $now - HOURSECS,
            ],
            'scheduledfor ASC',
            'id,status',
            0,
            $limit
        );

        $ids = [];
        foreach ($records as $record) {
            $current = $DB->get_record(
                'videotrackerpremium_notify',
                ['id' => $record->id],
                'id,status,timemodified',
                IGNORE_MISSING
            );
            if (!$current) {
                continue;
            }
            if ($current->status === 'queued' && (int)$current->timemodified > ($now - HOURSECS)) {
                continue;
            }
            if (!in_array($current->status, ['pending', 'queued'], true)) {
                continue;
            }

            $current->status = 'queued';
            $current->timemodified = $now;
            $DB->update_record('videotrackerpremium_notify', $current);
            $ids[] = (int)$current->id;
        }
        return $ids;
    }

    /**
     * Method send.
     *
     * @param int $notificationid Parameter notificationid.
     * @return void Return value.
     */
    public static function send(int $notificationid): void {
        $factory = \core\lock\lock_config::get_lock_factory('mod_videotrackerpremium');
        $lock = $factory->get_lock('notification_' . $notificationid, 0);
        if (!$lock) {
            return;
        }

        try {
            self::send_unlocked($notificationid);
        } finally {
            $lock->release();
        }
    }

    /**
     * Sends one notification while the per-notification lock is held.
     */
    private static function send_unlocked(int $notificationid): void {
        global $DB;

        $notification = $DB->get_record('videotrackerpremium_notify', ['id' => $notificationid]);
        if (!$notification || !in_array($notification->status, ['queued', 'pending'], true)) {
            return;
        }
        $activity = $DB->get_record('videotrackerpremium', ['id' => $notification->activityid], '*', MUST_EXIST);
        if ($notification->type !== 'manual' && empty($activity->remindersenabled)) {
            $notification->status = 'cancelled';
            $notification->lasterror = 'remindersdisabled';
            $notification->timemodified = time();
            $DB->update_record('videotrackerpremium_notify', $notification);
            return;
        }

        $cm = get_coursemodule_from_instance(
            'videotrackerpremium', $activity->id, $activity->course, false, MUST_EXIST
        );
        $context = context_module::instance($cm->id);
        $user = $DB->get_record('user', ['id' => $notification->userid, 'deleted' => 0], '*', IGNORE_MISSING);
        if (!$user || !is_enrolled($context, $user, 'mod/videotrackerpremium:view', true)) {
            $notification->status = 'cancelled';
            $notification->lasterror = 'recipientnotavailable';
            $notification->timemodified = time();
            $DB->update_record('videotrackerpremium_notify', $notification);
            return;
        }

        $batch = progress_service::get_progress_batch($activity, $context, [(int)$user->id]);
        $status = status_service::get_user_status($activity, $context, (int)$user->id, $batch[$user->id] ?? null);
        if ($notification->type !== 'completed' && ($status['waived'] || $status['compliance'] !== 'notcompleted')) {
            $notification->status = 'cancelled';
            $notification->lasterror = 'nolongerneeded';
            $notification->timemodified = time();
            $DB->update_record('videotrackerpremium_notify', $notification);
            return;
        }

        $course = get_course($activity->course);
        $deadline = $status['deadline']
            ? userdate($status['deadline'], get_string('strftimedatetime', 'langconfig'))
            : get_string('nodeadline', 'videotrackerpremium');
        $values = [
            'firstname' => $user->firstname,
            'activityname' => format_string($activity->name, true, ['context' => $context]),
            'deadline' => $deadline,
            'percent' => $status['percent'],
            'course' => format_string(
                $course->fullname,
                true,
                ['context' => \context_course::instance($course->id)]
            ),
        ];
        $body = message_template::render(message_template::body_for($activity, $notification->type), $values);
        $subject = message_template::subject_for($notification->type, $values['activityname']);

        $message = new message();
        $message->component = 'mod_videotrackerpremium';
        $message->name = 'reminders';
        $message->userfrom = core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = nl2br(s($body));
        $message->smallmessage = $body;
        $message->notification = 1;
        $message->contexturl = (new \moodle_url('/mod/videotrackerpremium/view.php', ['id' => $cm->id]))->out(false);
        $message->contexturlname = $values['activityname'];

        try {
            $messageid = message_send($message);
            if (!$messageid) {
                throw new \moodle_exception('error');
            }
            $notification->status = 'sent';
            $notification->timesent = time();
            $notification->attempts = (int)$notification->attempts + 1;
            $notification->lasterror = '';
            $notification->timemodified = time();
            $DB->update_record('videotrackerpremium_notify', $notification);

            $event = reminder_sent::create([
                'objectid' => $notification->id,
                'context' => $context,
                'relateduserid' => $user->id,
                'other' => ['activityid' => (int)$activity->id, 'type' => $notification->type],
            ]);
            $event->trigger();

            if ($notification->type === 'overdue') {
                $config = self::config($activity);
                $days = max(0, (int)($config['repeatlateevery'] ?? 0));
                if ($days > 0) {
                    self::queue((int)$activity->id, (int)$user->id, 'overdue', time() + ($days * DAYSECS));
                }
            }
        } catch (\Throwable $exception) {
            $notification->attempts = (int)$notification->attempts + 1;
            $notification->lasterror = clean_param($exception->getMessage(), PARAM_TEXT);
            $notification->timemodified = time();
            if ($notification->attempts < 3) {
                $notification->status = 'pending';
                $notification->scheduledfor = time() + HOURSECS;
            } else {
                $notification->status = 'failed';
            }
            $DB->update_record('videotrackerpremium_notify', $notification);
        }
    }
}
