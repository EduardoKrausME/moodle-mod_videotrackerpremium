<?php
defined('MOODLE_INTERNAL') || die;

$tasks = [
    [
        'classname' => '\mod_videotrackerpremium\task\process_reminders',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
