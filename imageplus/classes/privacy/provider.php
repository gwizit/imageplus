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
 * Privacy provider for Image Replacer plugin
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions {@link https://gwizit.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     G Wiz IT Solutions
 */

namespace local_imageplus\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation
 *
 * @package    local_imageplus
 * @copyright  2025 G Wiz IT Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Get metadata
     *
     * @param collection $collection Collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_imageplus_log',
            [
                'userid' => 'privacy:metadata:local_imageplus_log:userid',
                'searchterm' => 'privacy:metadata:local_imageplus_log:searchterm',
                'filesreplaced' => 'privacy:metadata:local_imageplus_log:filesreplaced',
                'timemodified' => 'privacy:metadata:local_imageplus_log:timemodified',
            ],
            'privacy:metadata:local_imageplus_log'
        );

        return $collection;
    }

    /**
     * Get contexts for user
     *
     * @param int $userid User ID
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                FROM {local_imageplus_log} log
                JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                WHERE log.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'contextlevel' => CONTEXT_SYSTEM,
        ]);

        return $contextlist;
    }

    /**
     * Export user data
     *
     * @param approved_contextlist $contextlist Context list
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        $logs = $DB->get_records('local_imageplus_log', ['userid' => $user->id]);

        foreach ($logs as $log) {
            $data = (object)[
                'searchterm' => $log->searchterm,
                'filesreplaced' => $log->filesreplaced,
                'dbfilesreplaced' => $log->dbfilesreplaced,
                'filesfailed' => $log->filesfailed,
                'dryrun' => $log->dryrun ? get_string('yes') : get_string('no'),
                'timecreated' => transform::datetime($log->timecreated),
                'timemodified' => transform::datetime($log->timemodified),
            ];

            writer::with_context(\context_system::instance())
                ->export_data([get_string('pluginname', 'local_imageplus'), $log->id], $data);
        }
    }

    /**
     * Delete data for all users in context
     *
     * @param \context $context Context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('local_imageplus_log');
        }
    }

    /**
     * Delete data for user
     *
     * @param approved_contextlist $contextlist Context list
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('local_imageplus_log', ['userid' => $userid]);
    }

    /**
     * Get users in context
     *
     * @param userlist $userlist User list
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $sql = "SELECT userid FROM {local_imageplus_log}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Delete data for users
     *
     * @param approved_userlist $userlist User list
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($sql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_imageplus_log', "userid $sql", $params);
    }
}
