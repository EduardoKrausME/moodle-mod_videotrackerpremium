<?php
namespace mod_videotrackerpremium\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videotrackerpremium\local\service\bulk_service;
use mod_videotrackerpremium\local\service\recipient_guard;
use mod_videotrackerpremium\task\bulk_action_batch;

/**
 * Applies an authorised bulk action.
 */
class bulk_action extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User id')
            ),
            'action' => new external_value(
                PARAM_ALPHA,
                'remind, extend, waive or unwaive'
            ),
            'value' => new external_value(
                PARAM_INT,
                'Action numeric value',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    public static function execute(
        int $cmid,
        array $userids,
        string $action,
        int $value = 0
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            compact('cmid', 'userids', 'action', 'value')
        );
        $cm = get_coursemodule_from_id(
            'videotrackerpremium',
            $params['cmid'],
            0,
            false,
            MUST_EXIST
        );
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_login($cm->course, false, $cm);

        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => $cm->instance],
            '*',
            MUST_EXIST
        );

        if (!in_array($params['action'], ['remind', 'extend', 'waive', 'unwaive'], true)) {
            throw new \invalid_parameter_exception('Invalid bulk action.');
        }
        if ($params['action'] === 'remind') {
            require_capability('mod/videotrackerpremium:sendreminders', $context);
        } else {
            require_capability('mod/videotrackerpremium:manageoverrides', $context);
        }

        $requested = array_values(array_unique(array_filter(array_map('intval', $params['userids']))));
        if (!$requested) {
            throw new \invalid_parameter_exception('No learners selected.');
        }

        $authorised = recipient_guard::filter_authorised($context, $requested, (int)$USER->id);
        sort($requested);
        sort($authorised);
        if ($requested !== $authorised) {
            throw new \required_capability_exception(
                $context,
                $params['action'] === 'remind'
                    ? 'mod/videotrackerpremium:sendreminders'
                    : 'mod/videotrackerpremium:manageoverrides',
                'nopermissions',
                ''
            );
        }

        if (count($requested) > 100) {
            foreach (array_chunk($requested, 100) as $chunk) {
                $task = new bulk_action_batch();
                $task->set_custom_data([
                    'activityid' => (int)$activity->id,
                    'userids' => $chunk,
                    'action' => $params['action'],
                    'value' => $params['value'],
                    'actorid' => (int)$USER->id,
                ]);
                \core\task\manager::queue_adhoc_task($task);
            }
            return ['affected' => $requested, 'count' => count($requested)];
        }

        $affected = bulk_service::execute(
            $activity,
            $context,
            $requested,
            $params['action'],
            $params['value'],
            (int)$USER->id
        );
        return ['affected' => $affected, 'count' => count($affected)];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'affected' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Affected user id')
            ),
            'count' => new external_value(PARAM_INT, 'Affected count'),
        ]);
    }
}
