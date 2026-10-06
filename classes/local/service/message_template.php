<?php
namespace mod_videotrackerpremium\local\service;

use stdClass;

/**
 * Safe plain-text message template renderer.
 */
class message_template {
    public const PLACEHOLDERS = ['firstname', 'activityname', 'deadline', 'percent', 'course'];

    public static function render(string $template, array $values): string {
        $replace = [];
        foreach (self::PLACEHOLDERS as $name) {
            $replace['{' . $name . '}'] = (string)($values[$name] ?? '');
        }
        return strtr($template, $replace);
    }

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
