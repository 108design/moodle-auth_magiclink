<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/user_api.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink\local;

defined('MOODLE_INTERNAL') || die();

/** Native user creation across supported Moodle versions. */
final class user_api {
    public static function create(\stdClass $user, bool $updatepassword = true, bool $triggerevent = true): int {
        global $CFG;
        if (method_exists(\core\user::class, 'create_user')) {
            return \core\user::create_user($user, $updatepassword, $triggerevent);
        }
        require_once($CFG->dirroot . '/user/lib.php');
        return user_create_user($user, $updatepassword, $triggerevent);
    }
}
