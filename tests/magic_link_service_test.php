<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for tests/magic_link_service_test.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink;

defined('MOODLE_INTERNAL') || die();

use auth_magiclink\local\magic_link_service;

/** Regression tests for the standalone login and local deep-link contract. */
final class magic_link_service_test extends \advanced_testcase {
    /** Capture native mail without SMTP; retain single-use links and delayed account creation. */
    public function test_request_email_contains_clickable_confirmation_link(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        set_config('auth', 'manual,magiclink');
        set_config('allownewaccounts', 1, 'auth_magiclink');
        $CFG->authpreventaccountcreation = false;
        $CFG->noemailever = false;
        $CFG->handlebounces = false;
        $CFG->noreplyaddress = 'noreply@example.com';
        $sink = $this->redirectEmails();
        magic_link_service::request('new-person@example.com', '/local/supportdesk/index.php', '127.0.0.1');
        $this->assertCount(1, $sink->get_messages());
        $body = quoted_printable_decode($sink->get_messages()[0]->body);
        $document = new \DOMDocument();
        @$document->loadHTML($body);
        $links = (new \DOMXPath($document))->query('//a[contains(@href, "/auth/magiclink/confirm.php")]');
        $this->assertSame(1, $links->length);
        $url = $links->item(0)->getAttribute('href');
        parse_str(parse_url($url, PHP_URL_QUERY), $parameters);
        $token = magic_link_service::token($parameters['token']);
        $this->assertNotNull($token);
        $this->assertSame('/local/supportdesk/index.php', $token->returnurl);
        $this->assertFalse($DB->record_exists('user', ['email' => 'new-person@example.com']));
        $sink->close();
    }

    public function test_disabled_provider_rejects_previously_issued_token(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('auth', 'manual');
        $token = (object) ['email' => 'person@example.invalid', 'tokenhash' => hash('sha256', 'issued'),
            'returnurl' => '/', 'requestiphash' => str_repeat('0', 64),
            'timecreated' => time(), 'timeexpires' => time() + 600];
        $token->id = $DB->insert_record('auth_magiclink_token', $token);
        try {
            magic_link_service::consume($token, 'Test', 'Person');
            $this->fail('Disabled provider consumed a token.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('authdisabled', $exception->errorcode);
        }
        $this->assertEmpty($DB->get_field('auth_magiclink_token', 'timeused', ['id' => $token->id]));
        $this->assertFalse($DB->record_exists('user', ['email' => $token->email]));
    }

    public function test_token_consumption_is_single_use_and_rolls_back_on_failure(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('auth', 'manual,magiclink');
        $user = $this->getDataGenerator()->create_user();
        $token = (object) ['email' => $user->email, 'tokenhash' => hash('sha256', 'issued'),
            'returnurl' => '/', 'requestiphash' => str_repeat('0', 64),
            'timecreated' => time(), 'timeexpires' => time() + 600];
        $token->id = $DB->insert_record('auth_magiclink_token', $token);
        $this->assertEquals($user->id, magic_link_service::consume($token)->id);
        try {
            magic_link_service::consume($token);
            $this->fail('A consumed token was reused.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('tokeninvalid', $exception->errorcode);
        }
        $this->assertEquals(1, $DB->count_records('user', ['email' => $user->email]));
    }

    public function test_token_consumption_finds_existing_account_case_insensitively(): void {
        global $DB;

        $this->resetAfterTest();
        set_config('auth', 'manual,magiclink');
        $user = $this->getDataGenerator()->create_user(['email' => 'Mixed.Case@example.invalid']);
        $token = (object) ['email' => 'mixed.case@example.invalid', 'tokenhash' => hash('sha256', 'case-token'),
            'returnurl' => '/', 'requestiphash' => str_repeat('1', 64),
            'timecreated' => time(), 'timeexpires' => time() + 600];
        $token->id = $DB->insert_record('auth_magiclink_token', $token);

        $this->assertSame((int) $user->id, (int) magic_link_service::consume($token)->id);
        $this->assertCount(1, magic_link_service::users_by_email('MIXED.CASE@example.invalid'));
    }

    public function test_safe_return_url_accepts_only_this_moodle_site(): void {
        global $CFG;

        $this->assertSame('/course/view.php?id=7',
            magic_link_service::safe_return_url('/course/view.php?id=7'));
        $this->assertSame('/course/view.php?id=7',
            magic_link_service::safe_return_url($CFG->wwwroot . '/course/view.php?id=7'));
        $this->assertSame('/', magic_link_service::safe_return_url('https://example.invalid/phishing'));
        $this->assertSame('/', magic_link_service::safe_return_url('//example.invalid/phishing'));
        $this->assertSame('/', magic_link_service::safe_return_url('/\\example.invalid/phishing'));
    }

    public function test_normal_login_page_entry_preserves_deep_link(): void {
        global $CFG;

        require_once(__DIR__ . '/../auth.php');
        $this->resetAfterTest();
        set_config('auth', 'manual,magiclink');
        set_config('showonloginpage', 1, 'auth_magiclink');

        $plugin = new class extends \auth_plugin_magiclink {
            protected function licence_allows_login(): bool {
                return true;
            }
        };
        $entries = $plugin->loginpage_idp_list(
            $CFG->wwwroot . '/mod/page/view.php?id=9'
        );

        $this->assertCount(1, $entries);
        $this->assertInstanceOf(\moodle_url::class, $entries[0]['url']);
        $this->assertSame('/mod/page/view.php?id=9', $entries[0]['url']->get_param('returnurl'));
        $this->assertSame('auth_magiclink', $entries[0]['authcomponent']);
        $this->assertSame('passwordless', $entries[0]['loginmethod']);
        $this->assertSame(get_string('loginmethoddescription', 'auth_magiclink'), $entries[0]['description']);
    }

    public function test_marketplace_login_entry_needs_no_activation(): void {
        require_once(__DIR__ . '/../auth.php');
        $this->resetAfterTest();
        set_config('auth', 'manual,magiclink');
        set_config('showonloginpage', 1, 'auth_magiclink');
        $this->assertCount(1, (new \auth_plugin_magiclink())->loginpage_idp_list('/'));
    }

    public function test_login_page_entry_can_be_disabled(): void {
        require_once(__DIR__ . '/../auth.php');
        $this->resetAfterTest();
        set_config('showonloginpage', 0, 'auth_magiclink');

        $this->assertSame([], (new \auth_plugin_magiclink())->loginpage_idp_list('/'));
    }
}
