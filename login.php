<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for login.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
require_once(__DIR__ . '/../../config.php');
\auth_magiclink\local\magic_link_service::require_enabled();
\auth_magiclink\local\licensing\gate::require();

use auth_magiclink\local\magic_link_service;

$returnurl = magic_link_service::safe_return_url(optional_param('returnurl', '', PARAM_RAW_TRIMMED));
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url($returnurl));
}
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    magic_link_service::request(required_param('email', PARAM_EMAIL), $returnurl, getremoteaddr() ?: 'unknown');
    $sent = true;
}
$url = new moodle_url('/auth/magiclink/login.php', ['returnurl' => $returnurl]);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('passwordlesslogin', 'auth_magiclink'));
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');

$render = static function() use ($url, $sent) {
    global $OUTPUT;
    ob_start();
    echo html_writer::tag('h1', get_string('passwordlesslogin', 'auth_magiclink'), ['class' => 'h3 mb-3']);
    if ($sent) {
        echo $OUTPUT->notification(get_string('emailsent', 'auth_magiclink'), 'success');
        echo html_writer::tag('p', get_string('checkmailbox', 'auth_magiclink'));
        echo html_writer::link($url, get_string('useanotheremail', 'auth_magiclink'), ['class' => 'btn btn-secondary']);
    } else {
        echo html_writer::tag('p', get_string('loginintro', 'auth_magiclink'));
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::tag('label', get_string('email'), ['for' => 'id_email']);
        echo html_writer::empty_tag('input', ['type' => 'email', 'name' => 'email', 'id' => 'id_email',
            'class' => 'form-control mb-3', 'required' => 'required', 'autocomplete' => 'email']);
        echo html_writer::tag('button', get_string('sendlink', 'auth_magiclink'),
            ['type' => 'submit', 'class' => 'btn btn-primary']);
        echo html_writer::end_tag('form');
    }
    if (!$sent) {echo html_writer::div(html_writer::link(get_login_url(), get_string('standardlogin', 'auth_magiclink'), ['class' => 'btn btn-secondary']), 'mt-3');}
    return ob_get_clean();
};
\auth_magiclink\local\access_ui::page($render);
