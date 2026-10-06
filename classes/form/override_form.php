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
 * override_form.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\form;

use core\context;
use moodleform;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Individual operational override form.
 */
class override_form extends moodleform {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
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
