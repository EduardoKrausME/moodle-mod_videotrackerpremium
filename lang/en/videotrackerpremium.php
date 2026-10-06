<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * videotrackerpremium.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activity_completed_late'] = 'Activity completed late';
$string['adminnote'] = 'Administrative note';
$string['all'] = 'All';
$string['allowlate'] = 'Allow viewing after deadline and grace period';
$string['applyselected'] = 'Apply to selected learners';
$string['availablefrom'] = 'Available from';
$string['bulkaction'] = 'Bulk action';
$string['bulkcompleted'] = 'The action was applied to {$a} learner(s).';
$string['bulkconfirm'] = 'Confirm the selected bulk action';
$string['bulkconfirmcount'] = 'This action will affect {$a} learner(s).';
$string['bulkqueued'] = 'The action for {$a} learner(s) was queued for background processing.';
$string['completed'] = 'Completed';
$string['completedlate'] = 'Completed late';
$string['completedontime'] = 'Completed on time';
$string['completion'] = 'Completion';
$string['completiondetail'] = 'Watch at least {$a}% of the video.';
$string['completionrequired'] = 'Require the minimum watched percentage for completion';
$string['complianceheader'] = 'Deadlines and compliance';
$string['continuewatching'] = 'Continue watching';
$string['currentpercent'] = 'Current progress';
$string['currentstatussummary'] = 'Current progress: {$a->percent}%. Operational status: {$a->status}.';
$string['dashboard'] = 'Operational dashboard';
$string['days'] = 'Days';
$string['deadline'] = 'Deadline';
$string['deadline_extended'] = 'Deadline extended';
$string['deadlineafteropen'] = 'The deadline must be after the availability date.';
$string['deadlineextendedcolumn'] = 'Deadline extended';
$string['deadlinefrom'] = 'Deadline from';
$string['deadlineto'] = 'Deadline to';
$string['deadlinevalue'] = 'Deadline: {$a}';
$string['details'] = 'Details';
$string['duesoon'] = 'Due soon';
$string['everyday'] = 'Every day';
$string['everyxdays'] = 'Every {$a} days';
$string['exportcsv'] = 'Export CSV';
$string['exportcsvwithhistory'] = 'Export CSV with administrative history';
$string['extenddeadline'] = 'Extend deadline';
$string['extended'] = 'Deadline extended';
$string['filterstatus'] = 'Status';
$string['graceperiod'] = 'Grace period';
$string['history'] = 'Administrative history';
$string['historydeadline'] = 'Deadline changed from {$a->old} to {$a->new}.';
$string['inprogress'] = 'In progress';
$string['invalidpercent'] = 'Enter a percentage from 1 to 100.';
$string['invalidrepeatinterval'] = 'Repeat interval must be between 0 and 3650 days.';
$string['lastaccess'] = 'Last access';
$string['lastreminder'] = 'Last reminder';
$string['lastsession'] = 'Last session';
$string['lateviewingblocked'] = 'The viewing period has ended. Contact the course team if you need an extension.';
$string['maximumpercentfilter'] = 'Maximum percent';
$string['messageavailabledefault'] = 'Hello {firstname}, {activityname} is now available in {course}. Deadline: {deadline}.';
$string['messagecompleted_default'] = 'Hello {firstname}, your completion of {activityname} has been confirmed at {percent}%.';
$string['messageheader'] = 'Message templates';
$string['messagenear_default'] = 'Hello {firstname}, {activityname} is approaching its deadline ({deadline}). Current progress: {percent}%.';
$string['messageoverdue_default'] = 'Hello {firstname}, {activityname} is overdue. Deadline: {deadline}. Current progress: {percent}%.';
$string['messageplaceholders'] = 'Message placeholders';
$string['messageplaceholders_help'] = 'Allowed placeholders: {firstname}, {activityname}, {deadline}, {percent}, {course}. No PHP or evaluated expressions are supported.';
$string['messageprovider:reminders'] = 'Reminders and compliance notifications';
$string['messagesubject_available'] = '{$a}: available';
$string['messagesubject_completed'] = '{$a}: completion confirmed';
$string['messagesubject_manual'] = '{$a}: reminder';
$string['messagesubject_near'] = '{$a}: deadline approaching';
$string['messagesubject_overdue'] = '{$a}: overdue';
$string['messagesubject_tomorrow'] = '{$a}: deadline tomorrow';
$string['messagetomorrow_default'] = 'Hello {firstname}, {activityname} is due tomorrow ({deadline}). Current progress: {percent}%.';
$string['minimumpercent'] = 'Minimum watched percentage';
$string['minimumpercent_help'] = 'Authoritative Video Bridge progress must reach this percentage for completion.';
$string['minimumpercentfilter'] = 'Minimum percent';
$string['modulename'] = 'Video Tracker Premium';
$string['modulenameplural'] = 'Video Tracker Premium activities';
$string['no'] = 'No';
$string['noactivities'] = 'There are no Video Tracker Premium activities in this course.';
$string['nodeadline'] = 'No deadline';
$string['norows'] = 'No learners match the selected filters.';
$string['notavailableyet'] = 'This video is not available yet.';
$string['notcompleted'] = 'Not completed';
$string['notrackingsources'] = 'No Video Bridge source with reliable tracking is installed.';
$string['notstarted'] = 'Not started';
$string['overdue'] = 'Overdue';
$string['overduefriendly'] = 'Overdue by {$a}.';
$string['override'] = 'Override';
$string['overridefor'] = 'Override for {$a}';
$string['overridesaved'] = 'The learner override was saved.';
$string['percent'] = 'Percent';
$string['playererror'] = 'The video player could not be initialised. Reload the page or contact the course team if the problem continues.';
$string['pluginadministration'] = 'Video Tracker Premium administration';
$string['pluginname'] = 'Video Tracker Premium';
$string['privacy:metadata:history'] = 'Administrative change history related to a learner.';
$string['privacy:metadata:notify'] = 'Reminder scheduling and delivery log.';
$string['privacy:metadata:override'] = 'Individual deadline, waiver and administrative note overrides.';
$string['privacy:metadata:state'] = 'Derived operational completion state based on Video Bridge progress.';
$string['receivedreminder'] = 'Received reminder';
$string['remainingfriendly'] = '{$a} remaining.';
$string['reminder1'] = 'Remind 1 day before';
$string['reminder3'] = 'Remind 3 days before';
$string['reminder7'] = 'Remind 7 days before';
$string['reminder_sent'] = 'Reminder sent';
$string['reminderafter1'] = 'Remind 1 day after';
$string['reminderday'] = 'Remind on the deadline';
$string['reminderheader'] = 'Reminders';
$string['remindersenabled'] = 'Enable reminders';
$string['removewaiver'] = 'Remove waiver';
$string['repeatlateevery'] = 'Repeat while overdue';
$string['report'] = 'Compliance report';
$string['requiredpercent'] = 'Required progress';
$string['sendavailable'] = 'Notify when the activity becomes available';
$string['sendremindernow'] = 'Send reminder now';
$string['status'] = 'Status';
$string['status_completed'] = 'Completed';
$string['status_duesoon'] = 'Due soon';
$string['status_duetoday'] = 'Due today';
$string['status_extended'] = 'Deadline extended';
$string['status_inprogress'] = 'In progress';
$string['status_notavailable'] = 'Not available';
$string['status_notstarted'] = 'Not started';
$string['status_overdue'] = 'Overdue';
$string['status_waived'] = 'Waived';
$string['student'] = 'Learner';
$string['taskprocessreminders'] = 'Process Video Tracker Premium reminders';
$string['templateavailable'] = 'Activity available';
$string['templatecompleted'] = 'Completion confirmed';
$string['templatenear'] = 'Deadline approaching';
$string['templateoverdue'] = 'Overdue';
$string['templatetomorrow'] = 'Deadline tomorrow';
$string['timeremaining'] = 'Time remaining';
$string['total'] = 'Total';
$string['user_waived'] = 'Learner waived';
$string['videoheader'] = 'Video';
$string['videosource'] = 'Video source';
$string['videotrackerpremium:addinstance'] = 'Add a Video Tracker Premium activity';
$string['videotrackerpremium:export'] = 'Export compliance reports';
$string['videotrackerpremium:manageoverrides'] = 'Manage learner overrides';
$string['videotrackerpremium:sendreminders'] = 'Send reminders';
$string['videotrackerpremium:view'] = 'View Video Tracker Premium';
$string['videotrackerpremium:viewadminhistory'] = 'View administrative history';
$string['videotrackerpremium:viewreport'] = 'View operational dashboard';
$string['videotrackerpremiumname'] = 'Activity name';
$string['waive'] = 'Waive';
$string['waived'] = 'Waived';
$string['waiver_removed'] = 'Waiver removed';
$string['yes'] = 'Yes';
