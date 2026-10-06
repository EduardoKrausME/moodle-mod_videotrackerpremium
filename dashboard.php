<?php
// This file is part of Moodle - http://moodle.org/.

require_once(__DIR__ . '/../../config.php');

use mod_videotrackerpremium\local\service\progress_service;
use mod_videotrackerpremium\local\service\recipient_guard;
use mod_videotrackerpremium\local\service\status_service;

$id = required_param('id', PARAM_INT);
$statusfilter = optional_param('status', '', PARAM_ALPHANUMEXT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$minpercent = optional_param('minpercent', 0, PARAM_INT);
$maxpercent = optional_param('maxpercent', 100, PARAM_INT);
$reminded = optional_param('reminded', '', PARAM_ALPHA);
$completionfilter = optional_param('completionfilter', '', PARAM_ALPHANUMEXT);
$deadlinefromraw = optional_param('deadlinefrom', '', PARAM_RAW_TRIMMED);
$deadlinetoraw = optional_param('deadlineto', '', PARAM_RAW_TRIMMED);

$cm = get_coursemodule_from_id('videotrackerpremium', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videotrackerpremium', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/videotrackerpremium:viewreport', $context);

$PAGE->set_url('/mod/videotrackerpremium/dashboard.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('dashboard', 'videotrackerpremium'));
$PAGE->set_heading(format_string($course->fullname));

/**
 * Converts an HTML date to the beginning/end of the user's day.
 *
 * @param string $value
 * @param bool $endofday
 * @return int
 */
function videotrackerpremium_filter_date(string $value, bool $endofday = false): int {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
        return 0;
    }
    return make_timestamp(
        (int)$matches[1],
        (int)$matches[2],
        (int)$matches[3],
        $endofday ? 23 : 0,
        $endofday ? 59 : 0,
        $endofday ? 59 : 0,
        99,
        false
    );
}

$deadlinefrom = videotrackerpremium_filter_date($deadlinefromraw);
$deadlineto = videotrackerpremium_filter_date($deadlinetoraw, true);

$users = get_enrolled_users(
    $context,
    'mod/videotrackerpremium:view',
    0,
    'u.id,u.firstname,u.lastname,u.email',
    null,
    0,
    0,
    true
);
$authorisedids = recipient_guard::filter_authorised(
    $context,
    array_keys($users),
    (int)$USER->id
);
$users = array_intersect_key($users, array_fill_keys($authorisedids, true));

$modinfo = get_fast_modinfo($course);
$cminfo = $modinfo->get_cm($cm->id);
$allowedgroups = groups_get_activity_allowed_groups($cminfo);
$groupoptions = [['value' => 0, 'label' => get_string('all', 'videotrackerpremium'), 'selected' => $groupid === 0]];
foreach ($allowedgroups as $group) {
    $groupoptions[] = [
        'value' => (int)$group->id,
        'label' => format_string($group->name),
        'selected' => (int)$group->id === $groupid,
    ];
}

if ($groupid) {
    if (!isset($allowedgroups[$groupid])) {
        throw new required_capability_exception(
            $context,
            'moodle/site:accessallgroups',
            'nopermissions',
            ''
        );
    }
    $members = groups_get_members($groupid, 'u.id');
    $users = array_intersect_key($users, $members);
}

$userids = array_map('intval', array_keys($users));
$progresses = progress_service::get_progress_batch($activity, $context, $userids);

$cards = [
    'total' => count($users),
    'completed' => 0,
    'inprogress' => 0,
    'notstarted' => 0,
    'duesoon' => 0,
    'overdue' => 0,
    'waived' => 0,
];

$rows = [];
foreach ($users as $userid => $user) {
    $userid = (int)$userid;
    $status = status_service::get_user_status(
        $activity,
        $context,
        $userid,
        $progresses[$userid] ?? null
    );

    switch ($status['status']) {
        case 'completed':
            $cards['completed']++;
            break;
        case 'inprogress':
        case 'extended':
            $cards['inprogress']++;
            break;
        case 'notstarted':
        case 'notavailable':
            $cards['notstarted']++;
            break;
        case 'duesoon':
        case 'duetoday':
            $cards['duesoon']++;
            break;
        case 'overdue':
            $cards['overdue']++;
            break;
        case 'waived':
            $cards['waived']++;
            break;
    }

    if ($statusfilter !== '' && $status['status'] !== $statusfilter) {
        continue;
    }
    if ($status['percent'] < $minpercent || $status['percent'] > $maxpercent) {
        continue;
    }
    if ($deadlinefrom && (!$status['deadline'] || $status['deadline'] < $deadlinefrom)) {
        continue;
    }
    if ($deadlineto && (!$status['deadline'] || $status['deadline'] > $deadlineto)) {
        continue;
    }
    if ($reminded === 'yes' && !$status['lastreminder']) {
        continue;
    }
    if ($reminded === 'no' && $status['lastreminder']) {
        continue;
    }
    if ($completionfilter !== '' && $status['compliance'] !== $completionfilter) {
        if (!($completionfilter === 'extended' && $status['extended'])) {
            continue;
        }
    }

    $rows[] = [
        'userid' => $userid,
        'fullname' => fullname($user),
        'email' => s($user->email),
        'percent' => (int)$status['percent'],
        'status' => get_string('status_' . $status['status'], 'videotrackerpremium'),
        'deadline' => $status['deadline']
            ? userdate($status['deadline'], get_string('strftimedatetime', 'langconfig'))
            : get_string('nodeadline', 'videotrackerpremium'),
        'lastaccess' => !empty($progresses[$userid]->timemodified)
            ? userdate((int)$progresses[$userid]->timemodified, get_string('strftimedatetime', 'langconfig'))
            : '-',
        'lastsession' => $status['lastsession']
            ? userdate($status['lastsession'], get_string('strftimedatetime', 'langconfig'))
            : '-',
        'lastreminder' => $status['lastreminder']
            ? userdate($status['lastreminder'], get_string('strftimedatetime', 'langconfig'))
            : '-',
        'completion' => get_string($status['compliance'], 'videotrackerpremium'),
        'overrideurl' => (new moodle_url('/mod/videotrackerpremium/override.php', [
            'id' => $cm->id,
            'userid' => $userid,
        ]))->out(false),
    ];
}

$statuses = [
    '' => get_string('all', 'videotrackerpremium'),
    'notavailable' => get_string('status_notavailable', 'videotrackerpremium'),
    'notstarted' => get_string('status_notstarted', 'videotrackerpremium'),
    'inprogress' => get_string('status_inprogress', 'videotrackerpremium'),
    'completed' => get_string('status_completed', 'videotrackerpremium'),
    'duesoon' => get_string('status_duesoon', 'videotrackerpremium'),
    'duetoday' => get_string('status_duetoday', 'videotrackerpremium'),
    'overdue' => get_string('status_overdue', 'videotrackerpremium'),
    'waived' => get_string('status_waived', 'videotrackerpremium'),
    'extended' => get_string('status_extended', 'videotrackerpremium'),
];
$statusoptions = [];
foreach ($statuses as $value => $label) {
    $statusoptions[] = ['value' => $value, 'label' => $label, 'selected' => $value === $statusfilter];
}

$completionoptions = [];
foreach ([
    '' => get_string('all', 'videotrackerpremium'),
    'completedontime' => get_string('completedontime', 'videotrackerpremium'),
    'completedlate' => get_string('completedlate', 'videotrackerpremium'),
    'notcompleted' => get_string('notcompleted', 'videotrackerpremium'),
    'waived' => get_string('status_waived', 'videotrackerpremium'),
    'extended' => get_string('status_extended', 'videotrackerpremium'),
] as $value => $label) {
    $completionoptions[] = [
        'value' => $value,
        'label' => $label,
        'selected' => $value === $completionfilter,
    ];
}

$remindedoptions = [];
foreach ([
    '' => get_string('all', 'videotrackerpremium'),
    'yes' => get_string('yes', 'videotrackerpremium'),
    'no' => get_string('no', 'videotrackerpremium'),
] as $value => $label) {
    $remindedoptions[] = [
        'value' => $value,
        'label' => $label,
        'selected' => $value === $reminded,
    ];
}

$data = [
    'cmid' => $cm->id,
    'sesskey' => sesskey(),
    'activityname' => format_string($activity->name),
    'cards' => [
        ['label' => get_string('total', 'videotrackerpremium'), 'value' => $cards['total']],
        ['label' => get_string('completed', 'videotrackerpremium'), 'value' => $cards['completed']],
        ['label' => get_string('inprogress', 'videotrackerpremium'), 'value' => $cards['inprogress']],
        ['label' => get_string('notstarted', 'videotrackerpremium'), 'value' => $cards['notstarted']],
        ['label' => get_string('duesoon', 'videotrackerpremium'), 'value' => $cards['duesoon']],
        ['label' => get_string('overdue', 'videotrackerpremium'), 'value' => $cards['overdue']],
        ['label' => get_string('waived', 'videotrackerpremium'), 'value' => $cards['waived']],
    ],
    'rows' => $rows,
    'hasrows' => (bool)$rows,
    'statusoptions' => $statusoptions,
    'groupoptions' => $groupoptions,
    'remindedoptions' => $remindedoptions,
    'completionoptions' => $completionoptions,
    'minpercent' => $minpercent,
    'maxpercent' => $maxpercent,
    'deadlinefrom' => s($deadlinefromraw),
    'deadlineto' => s($deadlinetoraw),
    'canoverrides' => has_capability('mod/videotrackerpremium:manageoverrides', $context),
    'cansend' => has_capability('mod/videotrackerpremium:sendreminders', $context),
    'canexport' => has_capability('mod/videotrackerpremium:export', $context),
    'canexporthistory' => has_capability('mod/videotrackerpremium:export', $context)
        && has_capability('mod/videotrackerpremium:viewadminhistory', $context),
    'reporturl' => (new moodle_url('/mod/videotrackerpremium/report.php', ['id' => $cm->id]))->out(false),
    'reporthistoryurl' => (new moodle_url('/mod/videotrackerpremium/report.php', [
        'id' => $cm->id,
        'includehistory' => 1,
    ]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videotrackerpremium/dashboard', $data);
echo $OUTPUT->footer();
