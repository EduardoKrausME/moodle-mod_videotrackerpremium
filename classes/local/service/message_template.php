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
 * message_template.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videotrackerpremium\local\service;

use stdClass;

/**
 * Safe plain-text message template renderer.
 */
class message_template {
    public const PLACEHOLDERS = ['firstname', 'activityname', 'deadline', 'percent', 'course'];

    /**
     * Method render.
     *
     * @param string $template Parameter template.
     * @param array $values Parameter values.
     * @return string Return value.
     */
    public static function render(string $template, array $values): string {
        $replace = [];
        foreach (self::PLACEHOLDERS as $name) {
            $replace['{' . $name . '}'] = (string)($values[$name] ?? '');
        }
        return strtr($template, $replace);
    }

    /**
     * Method body_for.
     *
     * @param stdClass $activity Parameter activity.
     * @param string $type Parameter type.
     * @return string Return value.
     */
    public static function body_for(stdClass $activity, string $type): string {
        $mapping = [
            'available' => ['messageavailable', 'messageavailabledefault'],
            'near' => ['messagenear', 'messagenear_default'],
            'tomorrow' => ['messagetomorrow', 'messagetomorrow_default'],
            'today' => ['messagenear', 'messagenear_default'],
            'overdue' => ['messageoverdue', 'messageoverdue_default'],
            'completed' => ['messagecompleted', 'messagecompleted_default'],
            'manual' => ['messagenear', 'messagenear_default'],
        ];
        [$field, $string] = $mapping[$type] ?? $mapping['manual'];
        $configured = trim((string)($activity->{$field} ?? ''));
        return $configured !== '' ? $configured : get_string($string, 'videotrackerpremium');
    }

    /**
     * Method subject_for.
     *
     * @param string $type Parameter type.
     * @param string $activityname Parameter activityname.
     * @return string Return value.
     */
    public static function subject_for(string $type, string $activityname): string {
        $key = match ($type) {
            'available' => 'messagesubject_available',
            'near', 'today' => 'messagesubject_near',
            'tomorrow' => 'messagesubject_tomorrow',
            'overdue' => 'messagesubject_overdue',
            'completed' => 'messagesubject_completed',
            default => 'messagesubject_manual',
        };
        return get_string($key, 'videotrackerpremium', $activityname);
    }
}
