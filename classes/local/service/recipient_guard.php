<?php
namespace mod_videotrackerpremium\local\service;

use context_module;

/**
 * Validates that operational actions only target authorised enrolled users.
 */
class recipient_guard {
    public static function filter_authorised(context_module $context, array $requested, ?int $actorid = null): array {
        global $USER;

        $actorid ??= (int)$USER->id;
        $requested = array_values(array_unique(array_filter(array_map('intval', $requested))));
        if (!$requested) {
            return [];
        }

        $enrolled = get_enrolled_users($context, 'mod/videotrackerpremium:view', 0, 'u.id');
        $allowed = array_fill_keys(array_map('intval', array_keys($enrolled)), true);

        $cm = get_coursemodule_from_id('videotrackerpremium', $context->instanceid, 0, false, MUST_EXIST);
        if (groups_get_activity_groupmode($cm) !== NOGROUPS &&
                !has_capability('moodle/site:accessallgroups', $context, $actorid)) {
            $groups = groups_get_all_groups($cm->course, $actorid, $cm->groupingid ?: 0);
            $groupusers = [];
            foreach ($groups as $group) {
                foreach (groups_get_members($group->id, 'u.id') as $member) {
                    $groupusers[(int)$member->id] = true;
                }
            }
            $allowed = array_intersect_key($allowed, $groupusers);
        }

        return array_values(array_filter($requested, static fn(int $id): bool => isset($allowed[$id])));
    }

    public static function require_authorised(context_module $context, int $userid, ?int $actorid = null): void {
        if (self::filter_authorised($context, [$userid], $actorid) !== [$userid]) {
            throw new \required_capability_exception(
                $context,
                'mod/videotrackerpremium:manageoverrides',
                'nopermissions',
                ''
            );
        }
    }
}
