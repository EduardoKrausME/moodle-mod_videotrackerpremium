<?php
defined('MOODLE_INTERNAL') || die;

$observers = [
    [
        'eventname' => '\local_video_bridge\event\progress_threshold_reached',
        'callback' => '\mod_videotrackerpremium\observer::progress_threshold_reached',
    ],
    [
        'eventname' => '\local_video_bridge\event\video_completed',
        'callback' => '\mod_videotrackerpremium\observer::video_completed',
    ],
];
