<?php
namespace mod_videotrackerpremium\form;

use core\context;
use moodleform;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Individual operational override form.
 */
class override_form extends moodleform {
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement(
            'date_time_selector',
            'deadline',
            get_string('deadline', 'videotrackerpremium'),
            ['optional' => true]
        );
        $mform->addElement(
            'selectyesno',
            'waived',
            get_string('waive', 'videotrackerpremium')
        );
        $mform->addElement(
            'textarea',
            'adminnote',
            get_string('adminnote', 'videotrackerpremium'),
            ['rows' => 5, 'cols' => 80]
        );
        $mform->setType('adminnote', PARAM_TEXT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $this->add_action_buttons(
            true,
            get_string('savechanges')
        );
    }
}
