<?php
namespace mod_videotrackerpremium\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videotrackerpremium\local\service\status_service;

/**
 * Returns the current learner operational status.
 */
class get_status extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
        ]);
    }

    public static function execute(int $cmid): array {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['cmid' => $cmid]
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
        require_capability('mod/videotrackerpremium:view', $context);

        $activity = $DB->get_record(
            'videotrackerpremium',
            ['id' => $cm->instance],
            '*',
            MUST_EXIST
        );
        return status_service::get_user_status(
            $activity,
            $context,
            (int)$USER->id
        );
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'userid' => new external_value(PARAM_INT, 'User id'),
            'percent' => new external_value(PARAM_INT, 'Authoritative percent'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Operational status'),
            'deadline' => new external_value(PARAM_INT, 'Effective deadline'),
            'extended' => new external_value(PARAM_BOOL, 'Has deadline override'),
            'waived' => new external_value(PARAM_BOOL, 'Waived'),
            'lastsession' => new external_value(PARAM_INT, 'Last known bridge progress update'),
            'lastreminder' => new external_value(PARAM_INT, 'Last reminder'),
            'completiontime' => new external_value(PARAM_INT, 'Completion time'),
            'compliance' => new external_value(PARAM_ALPHANUMEXT, 'Compliance category'),
        ]);
    }
}
