<?php
// This file is part of Moodle - http://moodle.org/.

require_once(__DIR__ . '/../../config.php');

use local_video_bridge\source\manager as source_manager;
use mod_videotrackerpremium\local\service\progress_service;
use mod_videotrackerpremium\local\service\status_service;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videotrackerpremium', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videotrackerpremium', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videotrackerpremium:view', $context);

$PAGE->set_url('/mod/videotrackerpremium/view.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);
}

progress_service::ensure_threshold($activity, $context);
$progresses = progress_service::get_progress_batch($activity, $context, [(int)$USER->id]);
$progress = $progresses[$USER->id] ?? null;
progress_service::sync_completion($activity, $context, (int)$USER->id, $progress);
$now = time();
$DB->set_field('vtrackpremium_state', 'lastaccess', $now, [
    'activityid' => (int)$activity->id,
    'userid' => (int)$USER->id,
]);
$status = status_service::get_user_status($activity, $context, (int)$USER->id, $progress);
$manager = new source_manager();
$maymanage = has_capability('mod/videotrackerpremium:viewreport', $context);
$available = empty($activity->availablefrom) || $now >= (int)$activity->availablefrom;
$hardclose = (int)$status['deadline'] > 0
    ? (int)$status['deadline'] + max(0, (int)$activity->graceperiod)
    : 0;
$lateblocked = !$activity->allowlate && $hardclose > 0 && $now > $hardclose;
$canwatch = $maymanage || ($available && !$lateblocked);

$player = null;
$clientconfig = null;
if ($canwatch) {
    $player = $manager->get_player_config($activity, $context);
    $clientconfig = $player;
    unset($clientconfig['sourcetemplate']);
    $player['sourcehtml'] = $OUTPUT->render_from_template(
        $player['sourcetemplate'],
        ['player' => $player]
    );
}

$deadlineformatted = $status['deadline']
    ? userdate($status['deadline'], get_string('strftimedatetime', 'langconfig'))
    : get_string('nodeadline', 'videotrackerpremium');

$remaining = '';
if ($status['deadline']) {
    $seconds = (int)$status['deadline'] - $now;
    if ($seconds > 0) {
        $remaining = get_string(
            'remainingfriendly',
            'videotrackerpremium',
            format_time($seconds, 1)
        );
    } else if ($status['compliance'] === 'notcompleted' && !$status['waived']) {
        $remaining = get_string(
            'overduefriendly',
            'videotrackerpremium',
            format_time(abs($seconds), 1)
        );
    }
}

$templatedata = [
    'name' => format_string($activity->name),
    'intro' => format_module_intro('videotrackerpremium', $activity, $cm->id),
    'hasintro' => trim((string)$activity->intro) !== '',
    'percent' => (int)$status['percent'],
    'requiredpercent' => (int)$activity->minimumpercent,
    'status' => get_string('status_' . $status['status'], 'videotrackerpremium'),
    'deadline' => $deadlineformatted,
    'remaining' => $remaining,
    'hasremaining' => $remaining !== '',
    'canwatch' => $canwatch,
    'notavailable' => !$available && !$maymanage,
    'lateblocked' => $lateblocked && !$maymanage,
    'player' => $player,
    'dashboardurl' => has_capability('mod/videotrackerpremium:viewreport', $context)
        ? (new moodle_url('/mod/videotrackerpremium/dashboard.php', ['id' => $cm->id]))->out(false)
        : '',
    'hasdashboard' => has_capability('mod/videotrackerpremium:viewreport', $context),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videotrackerpremium/view', $templatedata);

if ($canwatch && $clientconfig) {
    $PAGE->requires->js_call_amd('mod_videotrackerpremium/view', 'init', [
        'videotrackerpremium-player',
        $clientconfig,
    ]);
}

echo $OUTPUT->footer();
