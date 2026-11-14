<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for tests/mfa_bridge_test.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink;

defined('MOODLE_INTERNAL') || die();

use auth_magiclink\local\magic_link_service;
use auth_magiclink\local\mfa_bridge;
use tool_mfa\plugininfo\factor;

/** Native MFA regression cases for consumed links and authentication-session boundaries. */
final class mfa_bridge_test extends \advanced_testcase {
    private bool $startedphpsession = false;

    protected function setUp(): void {
        global $SESSION;
        parent::setUp();
        $this->resetAfterTest();
        // complete_user_login regenerates a real PHP session ID, including in a CLI PHPUnit fixture.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $sessiondata = $_SESSION;
            session_start(['use_cookies' => 0, 'cache_limiter' => '', 'save_path' => make_request_directory()]);
            $_SESSION = $sessiondata;
            $_SESSION['SESSION'] =& $SESSION;
            $this->startedphpsession = true;
        }
        set_config('auth', 'manual,magiclink');
        set_config('mfaemailconfirmation', 1, 'auth_magiclink');
        set_config('enabled', 1, 'tool_mfa');
        set_config('lockout', 10, 'tool_mfa');
        set_config('factor_order', 'token,totp,webauthn,email,grace', 'tool_mfa');
        foreach (factor::get_factors() as $item) {
            set_config('enabled', 0, 'factor_' . $item->name);
        }
        set_config('enabled', 1, 'factor_email');
        set_config('weight', 100, 'factor_email');
        set_config('duration', 1800, 'factor_email');
        $this->setUser($this->getDataGenerator()->create_user());
        mfa_bridge::reset_session();
    }

    protected function tearDown(): void {
        if ($this->startedphpsession && session_status() === PHP_SESSION_ACTIVE) {
            session_abort();
        }
        parent::tearDown();
    }

    private function token(\stdClass $user): \stdClass {
        global $DB;
        $token = (object)['email' => $user->email, 'tokenhash' => hash('sha256', random_bytes(32)),
            'returnurl' => '/my/', 'requestiphash' => str_repeat('0', 64),
            'timecreated' => time(), 'timeexpires' => time() + 600];
        $token->id = $DB->insert_record('auth_magiclink_token', $token);
        return $token;
    }

    public function test_confirmed_email_receives_only_email_weight_and_core_finishes_mfa(): void {
        global $USER, $SESSION, $DB;
        $this->assertTrue(mfa_bridge::confirm_email($USER, strtoupper($USER->email), false));
        $this->assertSame(factor::STATE_PASS, factor::get_factor('email')->get_state());
        $this->assertSame(100, \tool_mfa\manager::get_cumulative_weight());
        $this->assertEmpty($SESSION->tool_mfa_authenticated ?? null);
        \tool_mfa\manager::resolve_mfa_status();
        $this->assertTrue($SESSION->tool_mfa_authenticated);
        $this->assertGreaterThan(0, $DB->get_field('tool_mfa', 'lastverified', ['userid' => $USER->id, 'factor' => 'email']));
    }

    public function test_email_weight_cannot_satisfy_a_stronger_policy(): void {
        global $USER, $SESSION;
        set_config('weight', 50, 'factor_email');
        set_config('enabled', 1, 'factor_totp');
        set_config('weight', 50, 'factor_totp');
        $this->assertTrue(mfa_bridge::confirm_email($USER, $USER->email, false));
        $this->assertSame(50, \tool_mfa\manager::get_cumulative_weight());
        $this->assertFalse(\tool_mfa\manager::passed_enough_factors());
        $this->assertEmpty($SESSION->tool_mfa_authenticated ?? null);
    }

    public function test_disabled_integration_mfa_or_email_never_receives_credit(): void {
        global $USER, $SESSION;
        foreach ([['auth_magiclink', 'mfaemailconfirmation'], ['tool_mfa', 'enabled'], ['factor_email', 'enabled']] as $setting) {
            set_config($setting[1], 0, $setting[0]);
            $this->assertFalse(mfa_bridge::confirm_email($USER, $USER->email, true));
            $this->assertEmpty($SESSION->factor_email ?? null);
            $this->assertEmpty($SESSION->tool_mfa_factor_token ?? null);
            set_config($setting[1], 1, $setting[0]);
        }
    }

    public function test_confirmation_cannot_credit_another_account_or_mailbox(): void {
        global $USER, $SESSION;
        $other = $this->getDataGenerator()->create_user();
        $this->assertFalse(mfa_bridge::confirm_email($other, $other->email, false));
        $this->assertFalse(mfa_bridge::confirm_email($USER, $other->email, false));
        $this->assertEmpty($SESSION->factor_email ?? null);
    }

    public function test_revoked_email_remains_excluded(): void {
        global $USER, $DB;
        factor::get_factor('email')->get_active_user_factors($USER);
        $DB->set_field('tool_mfa', 'revoked', 1, ['userid' => $USER->id, 'factor' => 'email']);
        $this->assertFalse(mfa_bridge::confirm_email($USER, $USER->email, false));
    }

    public function test_locked_or_failed_email_is_not_overwritten(): void {
        global $USER, $DB, $SESSION;
        factor::get_factor('email')->get_active_user_factors($USER);
        $DB->set_field('tool_mfa', 'lockcounter', 10, ['userid' => $USER->id, 'factor' => 'email']);
        $this->assertFalse(mfa_bridge::confirm_email($USER, $USER->email, false));
        $this->assertSame(factor::STATE_LOCKED, $SESSION->factor_email);
        $DB->set_field('tool_mfa', 'lockcounter', 0, ['userid' => $USER->id, 'factor' => 'email']);
        $SESSION->factor_email = factor::STATE_FAIL;
        $this->assertFalse(mfa_bridge::confirm_email($USER, $USER->email, false));
        $this->assertSame(factor::STATE_FAIL, $SESSION->factor_email);
    }

    public function test_account_switch_clears_all_old_factor_states_even_with_credit_disabled(): void {
        global $USER, $SESSION;
        $other = $this->getDataGenerator()->create_user();
        $SESSION->tool_mfa_authenticated = true;
        $SESSION->factor_totp = factor::STATE_PASS;
        $SESSION->factor_email = factor::STATE_PASS;
        $SESSION->tool_mfa_factor_token = true;
        $SESSION->mfa_gracemode_recursive = true;
        $SESSION->unrelated_preference = 'keep';
        set_config('mfaemailconfirmation', 0, 'auth_magiclink');
        magic_link_service::complete_login($this->token($other));
        $this->assertEquals($other->id, $USER->id);
        foreach (['tool_mfa_authenticated', 'factor_totp', 'factor_email', 'tool_mfa_factor_token', 'mfa_gracemode_recursive'] as $name) {
            $this->assertFalse(property_exists($SESSION, $name), $name);
        }
        $this->assertSame('keep', $SESSION->unrelated_preference);
        $this->assertSame('/my/', $SESSION->wantsurl);
    }

    public function test_invalid_or_reused_links_cannot_reset_or_credit_a_session(): void {
        global $USER, $SESSION, $DB;
        $token = $this->token($USER);
        magic_link_service::complete_login($token);
        $SESSION->factor_totp = 'existing-state';
        try {
            magic_link_service::complete_login($token);
            $this->fail('A consumed token was reused.');
        } catch (\moodle_exception $e) {
            $this->assertSame('tokeninvalid', $e->errorcode);
        }
        $this->assertSame('existing-state', $SESSION->factor_totp);
        $expired = $this->token($USER);
        $DB->set_field('auth_magiclink_token', 'timeexpires', time() - 1, ['id' => $expired->id]);
        try {
            magic_link_service::complete_login($expired);
            $this->fail('An expired token was used.');
        } catch (\moodle_exception $e) {
            $this->assertSame('tokeninvalid', $e->errorcode);
        }
        $this->assertSame('existing-state', $SESSION->factor_totp);
    }

    public function test_new_account_receives_credit_only_after_creation_and_verified_link(): void {
        global $CFG, $USER;
        set_config('allownewaccounts', 1, 'auth_magiclink');
        $CFG->authpreventaccountcreation = false;
        $token = $this->token((object)['email' => 'new-mfa@example.invalid']);
        magic_link_service::complete_login($token, 'New', 'Person');
        $this->assertSame('new-mfa@example.invalid', $USER->email);
        $this->assertSame('magiclink', $USER->auth);
        $this->assertSame(factor::STATE_PASS, factor::get_factor('email')->get_state());
    }

    public function test_only_one_message_is_sent_for_email_link_and_native_mfa_completion(): void {
        global $CFG, $USER;
        $CFG->noemailever = false;
        $CFG->handlebounces = false;
        $CFG->noreplyaddress = 'noreply@example.com';
        $sink = $this->redirectEmails();
        magic_link_service::request($USER->email, '/my/', '127.0.0.1');
        $this->assertCount(1, $sink->get_messages());
        $body = quoted_printable_decode($sink->get_messages()[0]->body);
        preg_match('~[?&]token=([A-Za-z0-9_-]{43})~', $body, $matches);
        $this->assertNotEmpty($matches);
        magic_link_service::complete_login(magic_link_service::token($matches[1]));
        \tool_mfa\manager::resolve_mfa_status();
        $this->assertCount(1, $sink->get_messages());
        $sink->close();
    }

    public function test_device_trust_is_requested_only_by_explicit_choice_and_awaits_full_mfa(): void {
        global $USER, $SESSION;
        set_config('enabled', 1, 'factor_token');
        set_config('weight', 100, 'factor_token');
        set_config('expiry', 30 * DAYSECS, 'factor_token');
        set_config('weight', 50, 'factor_email');
        $this->assertSame(30 * DAYSECS, mfa_bridge::trusted_device_duration());
        $this->assertTrue(mfa_bridge::confirm_email($USER, $USER->email, false));
        $this->assertFalse(property_exists($SESSION, 'tool_mfa_factor_token'));
        $this->assertTrue(mfa_bridge::confirm_email($USER, $USER->email, true));
        $this->assertEquals(1, $SESSION->tool_mfa_factor_token);
        $this->assertEmpty($SESSION->tool_mfa_authenticated ?? null);
        $this->assertFalse(\tool_mfa\manager::passed_enough_factors());
    }
}
