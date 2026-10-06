# Video Tracker Premium

Video Tracker Premium is a Moodle activity for the operational side of mandatory video consumption: deadlines, reminders, overdue learners, individual exceptions, follow-up and compliance reporting.

Its question is intentionally different from the analytics-oriented Tracker modules:

> Who must watch, who already watched, and who needs follow-up?

Playback providers, the player, authoritative watched percentage, viewing maps and session telemetry belong to `local_video_bridge`. Video Tracker Premium does not implement provider-specific playback or maintain a second progress model.

## Main capabilities

- availability date, deadline, grace period and optional late viewing;
- configurable minimum authoritative watched percentage;
- reminders 7, 3 and 1 day before, on the deadline, after the deadline and recurring while overdue;
- Moodle Message API delivery, respecting Moodle messaging infrastructure and preferences;
- safe message templates with `{firstname}`, `{activityname}`, `{deadline}`, `{percent}` and `{course}`;
- operational statuses for not available, not started, in progress, completed, due soon, due today, overdue, waived and extended deadlines;
- operational dashboard with status, group, deadline, progress, reminder and completion filters;
- individual deadline extensions, waivers and administrative notes;
- auditable override history containing affected user, actor, old/new deadline, action and timestamp;
- bulk reminders, deadline extensions, waivers and waiver removal with explicit confirmation and enrolment/group validation;
- scheduled reminder processing and adhoc tasks for large learner/message batches;
- deterministic notification log preventing duplicate fixed reminders;
- Moodle completion based only on authoritative Video Bridge progress or an explicit waiver;
- learner view with current/required percentage, deadline, objective remaining-time text and continue-watching action;
- compliance CSV export, optionally including administrative history when the user has the dedicated capability;
- Moodle events for reminder sent, deadline extended, waiver changes and late completion;
- Privacy API, backup/restore, PHPUnit tests, Behat scenario and CI syntax checks.

## Dependency

Requires `local_video_bridge`:

https://github.com/EduardoKrausME/moodle-local_video_bridge

A selected source must advertise reliable tracking support because compliance decisions use server-side persisted progress rather than a percentage supplied by the browser.

Video Tracker Premium works with the current persisted progress API and periodically synchronises operational state through cron. An optional Video Bridge evolution adds registered thresholds plus public `progress_updated`, `progress_threshold_reached` and `video_completed` events so completion can react immediately without aggressive polling.

## Reminder processing

The scheduled task runs every five minutes. It synchronises authoritative progress, prepares deterministic reminder occurrences and sends bounded batches. Large learner or notification sets are moved to adhoc tasks, keeping normal page loads out of reminder processing.

Messages are sent through Moodle Message API only. The plugin does not call PHPMailer directly, and learners cannot provide arbitrary recipients or message bodies.

## Responsibility boundary

Video Tracker Premium owns operational policy: deadlines, reminders, exceptions, compliance state and administrative audit history.

Video Bridge owns media sources, adapters, player integration, persisted progress, viewing maps and session/analytics telemetry. Reminder rules deliberately stay out of the bridge so the same tracking infrastructure remains reusable by the rest of the Tracker family.
