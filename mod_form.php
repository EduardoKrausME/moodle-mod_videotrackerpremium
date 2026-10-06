<?php
// This file is part of Moodle - http://moodle.org/.

use local_video_bridge\source\manager;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity configuration form.
 */
class mod_videotrackerpremium_mod_form extends moodleform_mod {
    /**
     * Defines the form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $sources = new manager();
        $options = $sources->get_options(['tracking']);
        if (!$options) {
            throw new moodle_exception('notrackingsources', 'videotrackerpremium');
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videotrackerpremiumname', 'videotrackerpremium'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'videoheader', get_string('videoheader', 'videotrackerpremium'));
        $mform->addElement('select', 'videosource', get_string('videosource', 'videotrackerpremium'), $options);
        $mform->setDefault('videosource', $sources->get_default_source());
        $mform->setType('videosource', PARAM_PLUGIN);
        $sources->add_form_elements($mform);

        $mform->addElement('header', 'complianceheader', get_string('complianceheader', 'videotrackerpremium'));
        $mform->addElement('text', 'minimumpercent', get_string('minimumpercent', 'videotrackerpremium'), ['size' => 6]);
        $mform->setType('minimumpercent', PARAM_INT);
        $mform->setDefault('minimumpercent', 90);
        $mform->addHelpButton('minimumpercent', 'minimumpercent', 'videotrackerpremium');

        $mform->addElement('date_time_selector', 'availablefrom', get_string('availablefrom', 'videotrackerpremium'),
            ['optional' => true]);
        $mform->addElement('date_time_selector', 'deadline', get_string('deadline', 'videotrackerpremium'),
            ['optional' => true]);
        $mform->addElement('duration', 'graceperiod', get_string('graceperiod', 'videotrackerpremium'),
            ['optional' => true]);
        $mform->setDefault('graceperiod', 0);
        $mform->addElement('selectyesno', 'allowlate', get_string('allowlate', 'videotrackerpremium'));
        $mform->setDefault('allowlate', 1);

        $mform->addElement('header', 'reminderheader', get_string('reminderheader', 'videotrackerpremium'));
        $mform->addElement('selectyesno', 'remindersenabled', get_string('remindersenabled', 'videotrackerpremium'));
        $mform->setDefault('remindersenabled', 1);
        $mform->addElement('advcheckbox', 'sendavailable', get_string('sendavailable', 'videotrackerpremium'));
        $mform->addElement('advcheckbox', 'reminder7', get_string('reminder7', 'videotrackerpremium'));
        $mform->addElement('advcheckbox', 'reminder3', get_string('reminder3', 'videotrackerpremium'));
        $mform->addElement('advcheckbox', 'reminder1', get_string('reminder1', 'videotrackerpremium'));
        $mform->addElement('advcheckbox', 'reminderday', get_string('reminderday', 'videotrackerpremium'));
        $mform->addElement('advcheckbox', 'reminderafter1', get_string('reminderafter1', 'videotrackerpremium'));
        foreach (['reminder7', 'reminder3', 'reminder1', 'reminderday', 'reminderafter1'] as $field) {
            $mform->setDefault($field, 1);
            $mform->hideIf($field, 'remindersenabled', 'eq', 0);
        }
        $mform->hideIf('sendavailable', 'remindersenabled', 'eq', 0);
        $mform->addElement('select', 'repeatlateevery', get_string('repeatlateevery', 'videotrackerpremium'), [
            0 => get_string('never'),
            1 => get_string('everyday', 'videotrackerpremium'),
            2 => get_string('everyxdays', 'videotrackerpremium', 2),
            3 => get_string('everyxdays', 'videotrackerpremium', 3),
            7 => get_string('everyxdays', 'videotrackerpremium', 7),
        ]);
        $mform->setDefault('repeatlateevery', 3);
        $mform->hideIf('repeatlateevery', 'remindersenabled', 'eq', 0);

        $mform->addElement('header', 'messageheader', get_string('messageheader', 'videotrackerpremium'));
        foreach ([
            'messageavailable' => 'templateavailable',
            'messagenear' => 'templatenear',
            'messagetomorrow' => 'templatetomorrow',
            'messageoverdue' => 'templateoverdue',
            'messagecompleted' => 'templatecompleted',
        ] as $field => $label) {
            $mform->addElement('textarea', $field, get_string($label, 'videotrackerpremium'),
                ['rows' => 3, 'cols' => 80]);
            $mform->setType($field, PARAM_TEXT);
            $mform->addHelpButton($field, 'messageplaceholders', 'videotrackerpremium');
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Adds the custom completion rule.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $mform->addElement('advcheckbox', 'completionrequired',
            get_string('completionrequired', 'videotrackerpremium'));
        $mform->setDefault('completionrequired', 1);
        return ['completionrequired'];
    }

    /**
     * Reports whether the custom completion rule is enabled.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionrequired']);
    }

    /**
     * Restores source and reminder virtual fields.
     *
     * @param array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);
        if (!empty($this->_cm->id)) {
            (new manager())->prepare_form_data($defaultvalues, context_module::instance($this->_cm->id));
        }

        $config = json_decode((string)($defaultvalues['reminderconfig'] ?? ''), true) ?: [];
        $offsets = array_map('intval', $config['offsets'] ?? []);
        $defaultvalues['sendavailable'] = !empty($config['sendavailable']);
        $defaultvalues['reminder7'] = in_array(-7, $offsets, true);
        $defaultvalues['reminder3'] = in_array(-3, $offsets, true);
        $defaultvalues['reminder1'] = in_array(-1, $offsets, true);
        $defaultvalues['reminderday'] = in_array(0, $offsets, true);
        $defaultvalues['reminderafter1'] = in_array(1, $offsets, true);
        $defaultvalues['repeatlateevery'] = (int)($config['repeatlateevery'] ?? 0);
    }

    /**
     * Validates operational settings and the selected source.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $errors += (new manager())->validation($data, $files);

        $percent = (int)($data['minimumpercent'] ?? 0);
        if ($percent < 1 || $percent > 100) {
            $errors['minimumpercent'] = get_string('invalidpercent', 'videotrackerpremium');
        }
        if (!empty($data['availablefrom']) && !empty($data['deadline']) &&
                (int)$data['availablefrom'] >= (int)$data['deadline']) {
            $errors['deadline'] = get_string('deadlineafteropen', 'videotrackerpremium');
        }
        return $errors;
    }
}
