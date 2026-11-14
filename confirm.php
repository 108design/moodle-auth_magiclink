<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for confirm.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
require_once(__DIR__ . '/../../config.php');
\auth_magiclink\local\magic_link_service::require_enabled();
\auth_magiclink\local\licensing\gate::require();

use auth_magiclink\local\magic_link_service;
use auth_magiclink\local\mfa_bridge;

$raw = optional_param('token', '', PARAM_RAW_TRIMMED);
$token = magic_link_service::token($raw);
$error = $token ? '' : 'linkunavailable';
$existing = $token ? magic_link_service::users_by_email((string)$token->email, 'id') : [];
if (count($existing) > 1) {$error = 'linkunavailable';}
$needsnames = $token && !$existing;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    if ($error === '') {
        try {
            magic_link_service::complete_login($token,
                optional_param('firstname', '', PARAM_NOTAGS), optional_param('lastname', '', PARAM_NOTAGS),
                (bool)optional_param('trustdevice', 0, PARAM_BOOL));
            redirect(new moodle_url(magic_link_service::safe_return_url((string)$token->returnurl)));
        } catch (moodle_exception $exception) {
            if (!in_array($exception->errorcode, ['tokeninvalid', 'emailambiguous', 'accountunavailable',
                    'newaccountsdisabled', 'namesrequired'], true)) {throw $exception;}
            $error = $exception->errorcode === 'namesrequired' ? 'namesrequired' : 'linkunavailable';
        }
    }
}
$url = new moodle_url('/auth/magiclink/confirm.php', ['token' => $raw]);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string($needsnames ? 'completeaccount' : 'confirmsignin', 'auth_magiclink'));
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');

$requesturl = new moodle_url('/auth/magiclink/login.php', ['returnurl' =>
    magic_link_service::safe_return_url($token ? (string)$token->returnurl : '')]);
$render = static function() use ($url, $needsnames, $token, $error, $requesturl) {
    global $OUTPUT;
    ob_start();
    echo html_writer::tag('h1', get_string($needsnames ? 'completeaccount' : 'confirmsignin', 'auth_magiclink'), ['class' => 'h3 mb-3']);
    if ($error !== '') {
        echo $OUTPUT->notification(get_string($error, 'auth_magiclink'), 'warning');
        if ($error !== 'namesrequired') {
            echo html_writer::link($requesturl,
                get_string('requestnewlink', 'auth_magiclink'), ['class' => 'btn btn-primary']);
            return ob_get_clean();
        }
    }
    echo html_writer::tag('p', get_string($needsnames ? 'completeaccountintro' : 'confirmsigninintro',
        'auth_magiclink', s($token->email)));
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    if ($needsnames) {
        foreach (['firstname', 'lastname'] as $field) {
            echo html_writer::tag('label', get_string($field), ['for' => 'id_' . $field]);
            echo html_writer::empty_tag('input', ['name' => $field, 'id' => 'id_' . $field,
                'class' => 'form-control mb-3', 'required' => 'required', 'autocomplete' => $field === 'firstname'
                    ? 'given-name' : 'family-name']);
        }
    }
    $trustduration = mfa_bridge::trusted_device_duration();
    if ($trustduration > 0) {
        $checkbox = html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'trustdevice',
            'id' => 'id_trustdevice', 'value' => 1, 'class' => 'form-check-input']);
        $label = html_writer::tag('label', get_string('form:trust', 'factor_token', format_time($trustduration)),
            ['for' => 'id_trustdevice', 'class' => 'form-check-label']);
        echo html_writer::div($checkbox . $label, 'form-check mb-3');
    }
    echo html_writer::tag('button', get_string($needsnames ? 'createaccountcontinue' : 'signincontinue', 'auth_magiclink'),
        ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo html_writer::end_tag('form');
    return ob_get_clean();
};
\auth_magiclink\local\access_ui::page($render);
