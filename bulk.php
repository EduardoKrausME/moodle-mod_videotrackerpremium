<?php
// This file is part of Moodle - http://moodle.org/.

require_once(__DIR__ . '/../../config.php');

use mod_videotrackerpremium\local\service\bulk_service;
use mod_videotrackerpremium\local\service\recipient_guard;
use mod_videotrackerpremium\task\bulk_action_batch;

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$value = optional_param('value', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$userids = optional_param_array('userids', [], PARAM_INT);

$cm = get_coursemodule_from_id('videotrackerpremium', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videotrackerpremium', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_sesskey();

if (!in_array($action, ['remind', 'extend', 'waive', 'unwaive'], true)) {
    throw new invalid_parameter_exception('Invalid bulk action.');
}
$requiredcapability = $action === 'remind'
    ? 'mod/videotrackerpremium:sendreminders'
    : 'mod/videotrackerpremium:manageoverrides';
require_capability($requiredcapability, $context);

$userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
$authorised = recipient_guard::filter_authorised($context, $userids, (int)$USER->id);
sort($userids);
sort($authorised);
if (!$userids || $userids !== $authorised) {
    throw new required_capability_exception(
        $context,
        $requiredcapability,
        'nopermissions',
        ''
    );
}

$returnurl = new moodle_url('/mod/videotrackerpremium/dashboard.php', ['id' => $cm->id]);

if (!$confirm) {
    $PAGE->set_url('/mod/videotrackerpremium/bulk.php');
    $PAGE->set_context($context);
    $PAGE->set_title(get_string('bulkconfirm', 'videotrackerpremium'));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('bulkconfirm', 'videotrackerpremium'));
    echo html_writer::tag(
        'p',
        get_string('bulkconfirmcount', 'videotrackerpremium', count($userids))
    );
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => 'bulk.php']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => $action]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'value', 'value' => $value]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'confirm', 'value' => 1]);
    foreach ($userids as $userid) {
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'userids[]',
            'value' => $userid,
        ]);
    }
    echo html_writer::tag('button', get_string('confirm'), [
        'type' => 'submit',
        'class' => 'btn btn-primary me-2',
    ]);
    echo html_writer::link($returnurl, get_string('cancel'), ['class' => 'btn btn-secondary']);
    echo html_writer::end_tag('form');
    echo $OUTPUT->footer();
    exit;
}

if (count($userids) > 100) {
    foreach (array_chunk($userids, 100) as $chunk) {
        $task = new bulk_action_batch();
        $task->set_custom_data([
            'activityid' => (int)$activity->id,
            'userids' => $chunk,
            'action' => $action,
            'value' => $value,
            'actorid' => (int)$USER->id,
        ]);
        \core\task\manager::queue_adhoc_task($task);
    }
    redirect(
        $returnurl,
        get_string('bulkqueued', 'videotrackerpremium', count($userids)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$affected = bulk_service::execute(
    $activity,
    $context,
    $userids,
    $action,
    $value,
    (int)$USER->id
);
redirect(
    $returnurl,
    get_string('bulkcompleted', 'videotrackerpremium', count($affected)),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
