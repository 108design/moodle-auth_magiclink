<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for settings_page.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('authsettingmagiclink');

$url = new moodle_url('/auth/magiclink/settings_page.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'auth_magiclink'));
$PAGE->set_heading(get_string('pluginname', 'auth_magiclink'));
\auth_magiclink\local\settings_help::prepare();

$form = new \auth_magiclink\form\settings_form();
$showonloginpage = get_config('auth_magiclink', 'showonloginpage');
$allownewaccounts = get_config('auth_magiclink', 'allownewaccounts');
$allowsiteadmins = get_config('auth_magiclink', 'allowsiteadmins');
$form->set_data([
    'showonloginpage' => $showonloginpage === false ? 1 : (int) $showonloginpage,
    'allownewaccounts' => $allownewaccounts === false ? 1 : (int) $allownewaccounts,
    'linklifetime' => (int) (get_config('auth_magiclink', 'linklifetime') ?: 15),
    'allowsiteadmins' => $allowsiteadmins === false ? 0 : (int) $allowsiteadmins,
    'mfaemailconfirmation' => (int)!empty(get_config('auth_magiclink', 'mfaemailconfirmation')),
]);

if ($data = $form->get_data()) {
    set_config('showonloginpage', (int) !empty($data->showonloginpage), 'auth_magiclink');
    set_config('allownewaccounts', (int) !empty($data->allownewaccounts), 'auth_magiclink');
    set_config('linklifetime', (int) $data->linklifetime, 'auth_magiclink');
    set_config('allowsiteadmins', (int) !empty($data->allowsiteadmins), 'auth_magiclink');
    set_config('mfaemailconfirmation', (int)!empty($data->mfaemailconfirmation), 'auth_magiclink');
    redirect($url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo \auth_magiclink\local\admin_navigation::render('settings');
echo $OUTPUT->heading(get_string('pluginname', 'auth_magiclink'), 2);
$form->display();
echo \auth_magiclink\local\settings_help::render();
echo $OUTPUT->footer();
