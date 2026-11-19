<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * Send a queued email sign-in link.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink\task;

use auth_magiclink\local\magic_link_service;

defined('MOODLE_INTERNAL') || die();

/** Sends the requested link when the account is eligible. */
final class send_login_link extends \core\task\adhoc_task {
    public function get_name(): string {
        return get_string('tasksendloginlink', 'auth_magiclink');
    }

    public function execute(): void {
        $data = $this->get_custom_data();
        magic_link_service::deliver_request((int) $data->requestid, $data->lang);
    }
}
