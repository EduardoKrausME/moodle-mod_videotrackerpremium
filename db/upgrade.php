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
 * upgrade.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Executes Video Tracker Premium upgrade steps.
 *
 * @param int $oldversion Installed plugin version.
 * @return bool
 */
function xmldb_videotrackerpremium_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026100603) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('vtrackpremium_state');
        $field = new xmldb_field(
            'lastaccess',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'laststatus'
        );

        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026100603, 'videotrackerpremium');
    }

    if ($oldversion < 2026100604) {
        $dbman = $DB->get_manager();
        $renames = [
            'vtrackpremium_override' => 'videotrackerpremium_override',
            'vtrackpremium_history' => 'videotrackerpremium_history',
            'vtrackpremium_notify' => 'videotrackerpremium_notify',
            'vtrackpremium_state' => 'videotrackerpremium_state',
        ];

        foreach ($renames as $oldname => $newname) {
            $oldtable = new xmldb_table($oldname);
            $newtable = new xmldb_table($newname);
            if ($dbman->table_exists($oldtable) && !$dbman->table_exists($newtable)) {
                $dbman->rename_table($oldtable, $newname);
            }
        }

        upgrade_mod_savepoint(true, 2026100604, 'videotrackerpremium');
    }

    return true;
}
