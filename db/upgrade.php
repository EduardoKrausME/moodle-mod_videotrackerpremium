<?php
// This file is part of Moodle - http://moodle.org/.

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

    return true;
}
