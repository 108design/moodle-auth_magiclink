<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/privacy/provider.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/** Login tokens expire quickly and are erased by a scheduled task; no durable user profile is owned here. */
final class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('auth_magiclink_token', [
            'email' => 'privacy:metadata:auth_magiclink_token',
            'returnurl' => 'privacy:metadata:auth_magiclink_token',
            'requestiphash' => 'privacy:metadata:auth_magiclink_token',
        ], 'privacy:metadata:auth_magiclink_token');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $list = new contextlist();
        $emailequal = $DB->sql_equal('t.email', 'u.email', false);
        $list->add_from_sql(
            'SELECT ctx.id FROM {context} ctx JOIN {user} u ON u.id = :userid '
                . 'WHERE ctx.contextlevel = :level AND ctx.instanceid = 0 '
                . "AND EXISTS (SELECT 1 FROM {auth_magiclink_token} t WHERE {$emailequal})",
            ['userid' => $userid, 'level' => CONTEXT_SYSTEM]
        );
        return $list;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        if (!in_array(\context_system::instance()->id, $contextlist->get_contextids(), true)) {
            return;
        }
        $email = (string) $contextlist->get_user()->email;
        $emailequal = $DB->sql_equal('email', ':email', false);
        $records = array_values($DB->get_records_select('auth_magiclink_token', $emailequal, ['email' => $email],
            'timecreated ASC', 'id,returnurl,timeexpires,timeused,timecreated'));
        foreach ($records as $record) {
            $record->timecreated = transform::datetime($record->timecreated);
            $record->timeexpires = transform::datetime($record->timeexpires);
            $record->timeused = $record->timeused ? transform::datetime($record->timeused) : null;
        }
        writer::with_context(\context_system::instance())->export_data(
            [get_string('pluginname', 'auth_magiclink')], (object) ['loginlinks' => $records]);
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->delete_records('auth_magiclink_token');
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        if (in_array(\context_system::instance()->id, $contextlist->get_contextids(), true)) {
            $emailequal = $DB->sql_equal('email', ':email', false);
            $DB->delete_records_select('auth_magiclink_token', $emailequal,
                ['email' => (string) $contextlist->get_user()->email]);
        }
    }

    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $emailequal = $DB->sql_equal('t.email', 'u.email', false);
        $userlist->add_from_sql('id',
            "SELECT DISTINCT u.id FROM {user} u JOIN {auth_magiclink_token} t ON {$emailequal}", []);
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM || !$userlist->get_userids()) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED, 'userid');
        $emails = $DB->get_fieldset_sql("SELECT email FROM {user} WHERE id {$insql}", $params);
        $emailequal = $DB->sql_equal('email', ':email', false);
        foreach ($emails as $email) {
            $DB->delete_records_select('auth_magiclink_token', $emailequal, ['email' => $email]);
        }
    }
}
