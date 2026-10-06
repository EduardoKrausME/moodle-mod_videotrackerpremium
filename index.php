<?php
// This file is part of Moodle - http://moodle.org/.

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);

require_course_login($course);

$PAGE->set_url('/mod/videotrackerpremium/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('modulenameplural', 'videotrackerpremium'));
$PAGE->set_heading(format_string($course->fullname));

$instances = get_all_instances_in_course('videotrackerpremium', $course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'videotrackerpremium'));

if (!$instances) {
    echo $OUTPUT->notification(
        get_string('noactivities', 'videotrackerpremium'),
        \core\output\notification::NOTIFY_INFO
    );
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('name'),
    get_string('deadline', 'videotrackerpremium'),
    get_string('minimumpercent', 'videotrackerpremium'),
];

foreach ($instances as $instance) {
    $activity = $DB->get_record(
        'videotrackerpremium',
        ['id' => $instance->id],
        'id,name,deadline,minimumpercent',
        MUST_EXIST
    );
    $table->data[] = [
        html_writer::link(
            new moodle_url('/mod/videotrackerpremium/view.php', ['id' => $instance->coursemodule]),
            format_string($activity->name)
        ),
        $activity->deadline
            ? userdate($activity->deadline, get_string('strftimedatetime', 'langconfig'))
            : get_string('nodeadline', 'videotrackerpremium'),
        (int)$activity->minimumpercent . '%',
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
