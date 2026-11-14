<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for lib.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen
 * @license   108design source-available software license; see LICENSE.md
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
defined('MOODLE_INTERNAL') || die();

/** Current-page help in Moodle's standard navbar plugin area. */
function auth_magiclink_render_navbar_output(\renderer_base $renderer): string {
    return \auth_magiclink\local\settings_help::navbar($renderer);
}
