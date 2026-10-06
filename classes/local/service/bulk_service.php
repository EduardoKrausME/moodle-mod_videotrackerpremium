<?php
namespace mod_videotrackerpremium\local\service;

use context_module;
use stdClass;

/**
 * Shared validated implementation for bulk operations.
 */
class bulk_service {
    public static function execute(
        stdClass $activity,
        context_module $context,
        array $requestedids,
        string $action,
        int $value,
        int $actorid
    ): array {
        $userids = recipient_guard::filter_authorised($context, $requestedids, $actorid);
        if (!$userids) {
            return [];
        }

        foreach ($userids as $userid) {
            switch ($action) {
                case 'remind':
                    require_capability('mod/videotrackerpremium:sendreminders', $context, $actorid);
                    reminder_service::queue_manual((int)$activity->id, $userid);
                    break;
                case 'extend':
                    require_capability('mod/videotrackerpremium:manageoverrides', $context, $actorid);
                    $status = status_service::get_user_status($activity, $context, $userid);
                    $base = $status['deadline'] ?: time();
                    override_service::set_deadline(
                        $activity,
                        $context,
                        $userid,
                        $base + (max(1, $value) * DAYSECS),
                        $actorid
                    );
                    break;
                case 'waive':
                    require_capability('mod/videotrackerpremium:manageoverrides', $context, $actorid);
                    override_service::set_waiver($activity, $context, $userid, true, $actorid);
                    break;
                case 'unwaive':
                    require_capability('mod/videotrackerpremium:manageoverrides', $context, $actorid);
                    override_service::set_waiver($activity, $context, $userid, false, $actorid);
                    break;
                default:
                    throw new \invalid_parameter_exception('Invalid bulk action.');
            }
        }
        return $userids;
    }
}
