<?php
// This file is part of Moodle - http://moodle.org/.

require_once(__DIR__ . '/../../config.php');

use mod_videotrackerpremium\form\override_form;
use mod_videotrackerpremium\local\service\override_service;
use mod_videotrackerpremium\local\service\recipient_guard;
use mod_videotrackerpremium\local\service\status_service;

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$cm = get_coursemodule_from_id('videotrackerpremium', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videotrackerpremium', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/videotrackerpremium:manageoverrides', $context);
recipient_guard::require_authorised($context, $userid, (int)$USER->id);

$target = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
$current = $DB->get_record('vtrackpremium_override', [
    'activityid' => $activity->id,
    'userid' => $userid,
]);
$status = status_service::get_user_status($activity, $context, $userid);

$PAGE->set_url('/mod/videotrackerpremium/override.php', ['id' => $cm->id, 'userid' => $userid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('override', 'videotrackerpremium'));
$PAGE->set_heading(format_string($course->fullname));

$form = new override_form();
$form->set_data((object)[
    'id' => $cm->id,
    'userid' => $userid,
    'deadline' => $current && $current->deadline ? (int)$current->deadline : (int)$status['deadline'],
    'waived' => $current ? (int)$current->waived : 0,
    'adminnote' => $current ? (string)$current->adminnote : '',
]);

$returnurl = new moodle_url('/mod/videotrackerpremium/dashboard.php', ['id' => $cm->id]);
if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    require_sesskey();

    $olddeadline = $current && $current->deadline
        ? (int)$current->deadline
        : (int)$activity->deadline;
    $oldwaived = $current ? (bool)$current->waived : false;
    $oldnote = $current ? (string)$current->adminnote : '';

    if ((int)$data->deadline !== $olddeadline) {
        override_service::set_deadline(
            $activity,
            $context,
            $userid,
            (int)$data->deadline,
            (int)$USER->id
        );
    }
    if ((bool)$data->waived !== $oldwaived) {
        override_service::set_waiver(
            $activity,
            $context,
            $userid,
            (bool)$data->waived,
            (int)$USER->id
        );
    }
    if ((string)$data->adminnote !== $oldnote) {
        override_service::set_note(
            $activity,
            $context,
            $userid,
            (string)$data->adminnote,
            (int)$USER->id
        );
    }

    redirect(
        $returnurl,
        get_string('overridesaved', 'videotrackerpremium'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(
    get_string('overridefor', 'videotrackerpremium', fullname($target))
);
echo html_writer::div(
    get_string('currentstatussummary', 'videotrackerpremium', (object)[
        'percent' => $status['percent'],
        'status' => get_string('status_' . $status['status'], 'videotrackerpremium'),
    ]),
    'alert alert-info'
);
$form->display();

if (has_capability('mod/videotrackerpremium:viewadminhistory', $context)) {
    $history = $DB->get_records(
        'vtrackpremium_history',
        ['activityid' => $activity->id, 'userid' => $userid],
        'timecreated DESC'
    );
    if ($history) {
        echo $OUTPUT->heading(get_string('history', 'videotrackerpremium'), 3);
        $table = new html_table();
        $table->head = [
            get_string('date'),
            get_string('action'),
            get_string('user'),
            get_string('details', 'videotrackerpremium'),
        ];
        foreach ($history as $item) {
            $actor = $item->actorid
                ? $DB->get_record('user', ['id' => $item->actorid], '*', IGNORE_MISSING)
                : null;
            $details = (string)$item->details;
            if ($item->olddeadline || $item->newdeadline) {
                $details .= ($details !== '' ? ' — ' : '') .
                    get_string('historydeadline', 'videotrackerpremium', (object)[
                        'old' => $item->olddeadline
                            ? userdate($item->olddeadline, get_string('strftimedatetime', 'langconfig'))
                            : '-',
                        'new' => $item->newdeadline
                            ? userdate($item->newdeadline, get_string('strftimedatetime', 'langconfig'))
                            : '-',
                    ]);
            }
            $table->data[] = [
                userdate($item->timecreated, get_string('strftimedatetime', 'langconfig')),
                s($item->action),
                $actor ? fullname($actor) : '-',
                s($details),
            ];
        }
        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
