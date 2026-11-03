<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/task/cleanup_tokens.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink\task;

defined('MOODLE_INTERNAL') || die();

final class cleanup_tokens extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('taskcleanuptokens', 'auth_magiclink');
    }

    public function execute(): void {
        global $DB;
        $DB->delete_records_select('auth_magiclink_token',
            'timeexpires < :expired OR (timeused IS NOT NULL AND timeused < :used)', [
                'expired' => time() - DAYSECS,
                'used' => time() - HOURSECS,
            ]);
    }
}
