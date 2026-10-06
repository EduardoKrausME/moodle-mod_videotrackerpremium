<?php
// This file is part of Moodle - http://moodle.org/.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

use mod_videotrackerpremium\local\service\progress_service;
use mod_videotrackerpremium\local\service\recipient_guard;
use mod_videotrackerpremium\local\service\status_service;

$id = required_param('id', PARAM_INT);
$includehistory = optional_param('includehistory', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('videotrackerpremium', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videotrackerpremium', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/videotrackerpremium:export', $context);

$users = get_enrolled_users(
    $context,
    'mod/videotrackerpremium:view',
    0,
    'u.id,u.firstname,u.lastname,u.email'
);
$authorised = recipient_guard::filter_authorised(
    $context,
    array_keys($users),
    (int)$USER->id
);
$users = array_intersect_key($users, array_fill_keys($authorised, true));
$userids = array_map('intval', array_keys($users));
$progresses = progress_service::get_progress_batch($activity, $context, $userids);

$canhistory = $includehistory &&
    has_capability('mod/videotrackerpremium:viewadminhistory', $context);
$historybyuser = [];
if ($canhistory && $userids) {
    [$insql, $params] = $DB->get_in_or_equal(
        $userids,
        SQL_PARAMS_NAMED,
        'historyuser'
    );
    $history = $DB->get_records_select(
        'vtrackpremium_history',
        "activityid = :activityid AND userid {$insql}",
        ['activityid' => $activity->id] + $params,
        'timecreated ASC'
    );
    foreach ($history as $item) {
        $parts = [
            userdate($item->timecreated, get_string('strftimedatetime', 'langconfig')),
            (string)$item->action,
        ];
        if ($item->olddeadline || $item->newdeadline) {
            $parts[] = ($item->olddeadline
                ? userdate($item->olddeadline, get_string('strftimedatetime', 'langconfig'))
                : '-') .
                ' -> ' .
                ($item->newdeadline
                    ? userdate($item->newdeadline, get_string('strftimedatetime', 'langconfig'))
                    : '-');
        }
        if (trim((string)$item->details) !== '') {
            $parts[] = trim((string)$item->details);
        }
        $historybyuser[(int)$item->userid][] = implode(' | ', $parts);
    }
}

$csv = new csv_export_writer();
$csv->set_filename(
    clean_filename('videotrackerpremium-' . $activity->name . '-' . userdate(time(), '%Y%m%d'))
);

$headers = [
    get_string('student', 'videotrackerpremium'),
    get_string('email'),
    get_string('percent', 'videotrackerpremium'),
    get_string('status', 'videotrackerpremium'),
    get_string('deadline', 'videotrackerpremium'),
    get_string('lastsession', 'videotrackerpremium'),
    get_string('lastreminder', 'videotrackerpremium'),
    get_string('completion', 'videotrackerpremium'),
    get_string('deadlineextendedcolumn', 'videotrackerpremium'),
];
if ($canhistory) {
    $headers[] = get_string('history', 'videotrackerpremium');
}
$csv->add_data($headers);

foreach ($users as $userid => $user) {
    $userid = (int)$userid;
    $status = status_service::get_user_status(
        $activity,
        $context,
        $userid,
        $progresses[$userid] ?? null
    );

    $compliance = $status['compliance'];
    if ($compliance === 'notcompleted' && $status['extended']) {
        $compliance = 'extended';
    }

    $row = [
        fullname($user),
        $user->email,
        $status['percent'] . '%',
        get_string('status_' . $status['status'], 'videotrackerpremium'),
        $status['deadline']
            ? userdate($status['deadline'], get_string('strftimedatetime', 'langconfig'))
            : get_string('nodeadline', 'videotrackerpremium'),
        $status['lastsession']
            ? userdate($status['lastsession'], get_string('strftimedatetime', 'langconfig'))
            : '',
        $status['lastreminder']
            ? userdate($status['lastreminder'], get_string('strftimedatetime', 'langconfig'))
            : '',
        get_string($compliance, 'videotrackerpremium'),
        $status['extended']
            ? get_string('yes', 'videotrackerpremium')
            : get_string('no', 'videotrackerpremium'),
    ];
    if ($canhistory) {
        $row[] = implode("\n", $historybyuser[$userid] ?? []);
    }
    $csv->add_data($row);
}

$csv->download_file();
exit;
