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
 * services.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_videotrackerpremium_bulk_action' => [
        'classname' => '\mod_videotrackerpremium\external\bulk_action',
        'methodname' => 'execute',
        'description' => 'Apply an authorised operational action to selected learners.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_videotrackerpremium_get_status' => [
        'classname' => '\mod_videotrackerpremium\external\get_status',
        'methodname' => 'execute',
        'description' => 'Return the current learner operational status.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/videotrackerpremium:view',
    ],
];
