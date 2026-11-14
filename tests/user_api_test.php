<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for tests/user_api_test.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink;

defined('MOODLE_INTERNAL') || die();

/** User-creation flags and token consumption through the actual native API. */
final class user_api_test extends \advanced_testcase {
    public function test_native_rejected_mail_invalidates_token_but_keeps_request(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        set_config('auth', 'manual,magiclink');
        set_config('allownewaccounts', 1, 'auth_magiclink');
        $CFG->authpreventaccountcreation = false;
        $CFG->noemailever = false;
        $CFG->divertallemailsto = 'invalid-address';
        $sink = $this->redirectEmails();
        \auth_magiclink\local\magic_link_service::request('undelivered@example.test', '/', '127.0.0.1');
        $this->assertDebuggingCalled();
        $this->assertCount(0, $sink->get_messages());
        $this->assertSame(1, $DB->count_records('auth_magiclink_token'));
        $this->assertNotEmpty($DB->get_field('auth_magiclink_token', 'timeused', []));
        $this->assertFalse($DB->record_exists('user', ['email' => 'undelivered@example.test']));
    }

    public function test_new_magiclink_account_has_no_password_event_and_token_is_single_use(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        set_config('auth', 'manual,magiclink');
        set_config('allownewaccounts', 1, 'auth_magiclink');
        $CFG->authpreventaccountcreation = false;
        $token = (object) ['email' => 'new-account@example.test', 'tokenhash' => hash('sha256', 'new-token'),
            'returnurl' => '/', 'requestiphash' => str_repeat('2', 64),
            'timecreated' => time(), 'timeexpires' => time() + 600];
        $token->id = $DB->insert_record('auth_magiclink_token', $token);
        $events = $this->redirectEvents();
        $user = \auth_magiclink\local\magic_link_service::consume($token, 'Test', 'Person');
        $this->assertSame('magiclink', $user->auth);
        $this->assertSame(AUTH_PASSWORD_NOT_CACHED, $user->password);
        $this->assertSame('new-account@example.test', $user->email);
        $this->assertNotEmpty($DB->get_field('auth_magiclink_token', 'timeused', ['id' => $token->id]));
        $createdevents = array_filter($events->get_events(), static fn($event) =>
            $event instanceof \core\event\user_created);
        $this->assertCount(0, $createdevents);
        $this->expectException(\moodle_exception::class);
        \auth_magiclink\local\magic_link_service::consume($token, 'Test', 'Person');
    }
}
