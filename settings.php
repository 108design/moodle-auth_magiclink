<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for settings.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Replace the generated auth settings form with a full-width product page so its tabs stay above the title.
    $settings = null;
    $ADMIN->add('authsettings', new admin_externalpage(
        'authsettingmagiclink',
        get_string('pluginname', 'auth_magiclink'),
        new moodle_url('/auth/magiclink/settings_page.php')
    ));
}
