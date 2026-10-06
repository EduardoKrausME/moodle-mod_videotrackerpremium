<?php
defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_videotrackerpremium_bulk_action' => [
        'classname' => '\mod_videotrackerpremium\external\bulk_action',
        'methodname' => 'execute',
        'description' => 'Apply an authorised operational action to selected learners.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_videotrackerpremium_get_status' => [
        'classname' => '\mod_videotrackerpremium\external\get_status',
        'methodname' => 'execute',
        'description' => 'Return the current learner operational status.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/videotrackerpremium:view',
    ],
];
