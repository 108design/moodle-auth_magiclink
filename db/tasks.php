<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for db/tasks.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
defined('MOODLE_INTERNAL') || die();

$tasks = [[
    'classname' => '\\auth_magiclink\\task\\cleanup_tokens',
    'blocking' => 0,
    'minute' => '17',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
]];
