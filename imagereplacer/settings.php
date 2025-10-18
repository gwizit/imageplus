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
 * Plugin settings
 *
 * @package    local_imagereplacer
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_imagereplacer', get_string('settingstitle', 'local_imagereplacer'));

    // Default search term.
    $settings->add(new admin_setting_configtext(
        'local_imagereplacer/defaultsearchterm',
        get_string('defaultsearchterm', 'local_imagereplacer'),
        get_string('defaultsearchterm_desc', 'local_imagereplacer'),
        '',
        PARAM_TEXT
    ));

    // Default execution mode.
    $settings->add(new admin_setting_configcheckbox(
        'local_imagereplacer/defaultmode',
        get_string('defaultmode', 'local_imagereplacer'),
        get_string('defaultmode_desc', 'local_imagereplacer'),
        1
    ));

    // Default preserve permissions.
    $settings->add(new admin_setting_configcheckbox(
        'local_imagereplacer/defaultpreservepermissions',
        get_string('defaultpreservepermissions', 'local_imagereplacer'),
        get_string('defaultpreservepermissions_desc', 'local_imagereplacer'),
        1
    ));

    // Default search database.
    $settings->add(new admin_setting_configcheckbox(
        'local_imagereplacer/defaultsearchdatabase',
        get_string('defaultsearchdatabase', 'local_imagereplacer'),
        get_string('defaultsearchdatabase_desc', 'local_imagereplacer'),
        1
    ));

    // Default search file system.
    $settings->add(new admin_setting_configcheckbox(
        'local_imagereplacer/defaultsearchfilesystem',
        get_string('defaultsearchfilesystem', 'local_imagereplacer'),
        get_string('defaultsearchfilesystem_desc', 'local_imagereplacer'),
        1
    ));

    $ADMIN->add('localplugins', $settings);

    // Add link to the tool in the site administration menu.
    // Try 'server' first (Moodle 5.x), fall back to 'tools' if it doesn't exist
    if ($ADMIN->locate('server')) {
        $ADMIN->add('server',
            new admin_externalpage(
                'local_imagereplacer_tool',
                get_string('pluginname', 'local_imagereplacer'),
                new moodle_url('/local/imagereplacer/index.php'),
                'local/imagereplacer:view'
            )
        );
    } else {
        $ADMIN->add('tools',
            new admin_externalpage(
                'local_imagereplacer_tool',
                get_string('pluginname', 'local_imagereplacer'),
                new moodle_url('/local/imagereplacer/index.php'),
                'local/imagereplacer:view'
            )
        );
    }
}
