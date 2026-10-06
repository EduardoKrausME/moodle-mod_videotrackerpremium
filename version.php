<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

$plugin->component = 'mod_videotrackerpremium';
$plugin->version = 2026100602;
$plugin->release = '1.0.2';
$plugin->requires = 2024100700;
$plugin->maturity = MATURITY_ALPHA;
$plugin->dependencies = [
    'local_video_bridge' => 2026100618,
];
