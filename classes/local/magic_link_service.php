<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/magic_link_service.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink\local;

defined('MOODLE_INTERNAL') || die();

use auth_magiclink\task\send_login_link;

/** Creates and consumes single-use email links without exposing account existence. */
final class magic_link_service {
    private const DEFAULT_TTL = 900;
    private const MAX_REQUESTS_PER_HOUR = 8;
    private const PENDING_PREFIX = 'pending:';

    /** Apply Moodle's provider switch at every entry point, including already issued links. */
    public static function require_enabled(): void {
        if (!is_enabled_auth('magiclink')) {
            throw new \moodle_exception('authdisabled', 'auth_magiclink');
        }
    }

    public static function request(string $email, string $returnurl, string $remoteaddress): void {
        global $DB;

        self::require_enabled();
        $email = \core_text::strtolower(trim($email));
        if (!validate_email($email) || \core_text::strlen($email) > 100) {
            throw new \moodle_exception('invalidemail');
        }
        $returnurl = self::safe_return_url($returnurl);
        $iphash = hash('sha256', "auth_magiclink\0" . $remoteaddress);
        $factory = \core\lock\lock_config::get_lock_factory('auth_magiclink');
        $iplock = $factory->get_lock('request-ip:' . $iphash, 2);
        if (!$iplock) {
            return;
        }
        try {
            $emaillock = $factory->get_lock('request-email:' . hash('sha256', $email), 2);
            if (!$emaillock) {
                return;
            }
            try {
                self::queue_request($email, $returnurl, $iphash);
            } finally {
                $emaillock->release();
            }
        } finally {
            $iplock->release();
        }
    }

    /** Reserve the request in the same transaction as the task, before checking account eligibility. */
    private static function queue_request(string $email, string $returnurl, string $iphash): void {
        global $DB;

        $now = time();
        $recent = $DB->count_records_select('auth_magiclink_token',
            '(email = :email OR requestiphash = :iphash) AND timecreated >= :since', [
                'email' => $email,
                'iphash' => $iphash,
                'since' => $now - HOURSECS,
            ]);
        if ($recent >= self::MAX_REQUESTS_PER_HOUR) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            $requestid = $DB->insert_record('auth_magiclink_token', (object) [
                'tokenhash' => self::PENDING_PREFIX . bin2hex(random_bytes(28)),
                'email' => $email,
                'returnurl' => $returnurl,
                'requestiphash' => $iphash,
                'timeexpires' => $now + self::ttl(),
                'timecreated' => $now,
            ]);
            $task = new send_login_link();
            $task->set_custom_data(['requestid' => $requestid, 'lang' => current_language()]);
            if (!\core\task\manager::queue_adhoc_task($task)) {
                throw new \coding_exception('Unable to queue the Magic Link request.');
            }
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }
    }

    /** Process a queued request. The queue holds a record ID and language, never a login secret. */
    public static function deliver_request(int $requestid, string $lang): void {
        global $DB, $SITE;

        $request = $DB->get_record('auth_magiclink_token', ['id' => $requestid]);
        if (!$request || !str_starts_with($request->tokenhash, self::PENDING_PREFIX)
                || !empty($request->timeused)) {
            return;
        }
        if (!is_enabled_auth('magiclink') || (int) $request->timeexpires < time()
                || !self::email_allowed($request->email)) {
            $DB->set_field('auth_magiclink_token', 'timeused', time(), ['id' => $requestid]);
            return;
        }

        $token = self::base64url(random_bytes(32));
        $ttl = self::ttl();
        $url = new \moodle_url('/auth/magiclink/confirm.php', ['token' => $token]);
        $recipient = self::recipient($request->email);
        $strings = get_string_manager();
        $subject = $strings->get_string('emailsubject', 'auth_magiclink', format_string($SITE->shortname), $lang);
        $body = $strings->get_string('emailbody', 'auth_magiclink', [
            'site' => format_string($SITE->fullname),
            'url' => $url->out(false),
            'minutes' => (int) ($ttl / MINSECS),
        ], $lang);
        $html = str_replace(s($url->out(false)), \html_writer::link($url, s($url->out(false))),
            text_to_html(s($body), false, false, true));

        // An ad hoc task retry must not create a second link after an attempted delivery.
        $DB->update_record('auth_magiclink_token', (object) [
            'id' => $requestid,
            'tokenhash' => hash('sha256', $token),
            'timeexpires' => time() + $ttl,
        ]);
        $sent = false;
        try {
            $sent = email_to_user($recipient, \core_user::get_noreply_user(), $subject, $body, $html);
        } finally {
            if (!$sent) {
                // Keep the request for rate limiting, but never accept an undelivered login token.
                $DB->set_field('auth_magiclink_token', 'timeused', time(), ['id' => $requestid]);
            }
        }
    }

    private static function email_allowed(string $email): bool {
        $users = self::users_by_email($email);
        if (count($users) > 1) {
            return false;
        }
        if ($users) {
            return self::user_allowed(reset($users));
        }
        return self::account_creation_allowed() && !email_is_not_allowed($email);
    }

    public static function token(string $raw): ?\stdClass {
        global $DB;
        self::require_enabled();
        if (preg_match('/^[A-Za-z0-9_-]{43}$/D', $raw) !== 1) {
            return null;
        }
        $token = $DB->get_record('auth_magiclink_token', ['tokenhash' => hash('sha256', $raw)]);
        if (!$token || !empty($token->timeused) || (int) $token->timeexpires < time()) {
            return null;
        }
        return $token;
    }

    public static function consume(\stdClass $token, ?string $firstname = null, ?string $lastname = null): \stdClass {
        global $DB;
        self::require_enabled();
        $token = $DB->get_record('auth_magiclink_token', ['id' => $token->id], '*', MUST_EXIST);
        // Serialise all tokens for the same email, including concurrent new-account creation.
        $lock = \core\lock\lock_config::get_lock_factory('auth_magiclink')->get_lock(
            'consume:' . hash('sha256', (string) $token->email), 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }
        try {
            $transaction = $DB->start_delegated_transaction();
            try {
                $user = self::consume_locked($token, $firstname, $lastname);
                $transaction->allow_commit();
                return $user;
            } catch (\Throwable $exception) {
                $transaction->rollback($exception);
            }
        } finally {
            $lock->release();
        }
    }

    /** Complete a verified link login with fresh MFA state and optional native email credit. */
    public static function complete_login(\stdClass $token, ?string $firstname = null,
            ?string $lastname = null, bool $trustdevice = false): \stdClass {
        global $SESSION;

        // An invalid, expired or reused link must never change authentication state.
        $user = self::consume($token, $firstname, $lastname);
        mfa_bridge::reset_session();
        complete_user_login($user);
        $SESSION->wantsurl = self::safe_return_url((string)$token->returnurl);
        mfa_bridge::confirm_email($user, (string)$token->email, $trustdevice);
        return $user;
    }

    private static function consume_locked(\stdClass $token, ?string $firstname, ?string $lastname): \stdClass {
        global $CFG, $DB;

        self::require_enabled();
        $token = $DB->get_record('auth_magiclink_token', ['id' => $token->id], '*', MUST_EXIST);
        if (!empty($token->timeused) || (int) $token->timeexpires < time()
                || str_starts_with($token->tokenhash, self::PENDING_PREFIX)) {
            throw new \moodle_exception('tokeninvalid', 'auth_magiclink');
        }
        $users = self::users_by_email((string) $token->email);
        if (count($users) > 1) {
            throw new \moodle_exception('emailambiguous', 'auth_magiclink');
        }
        if ($users) {
            $user = reset($users);
            if (!self::user_allowed($user)) {
                throw new \moodle_exception('accountunavailable', 'auth_magiclink');
            }
        } else {
            if (!self::account_creation_allowed() || email_is_not_allowed((string) $token->email)) {
                throw new \moodle_exception('newaccountsdisabled', 'auth_magiclink');
            }
            $firstname = clean_param(trim((string) $firstname), PARAM_NOTAGS);
            $lastname = clean_param(trim((string) $lastname), PARAM_NOTAGS);
            if ($firstname === '' || $lastname === '') {
                throw new \moodle_exception('namesrequired', 'auth_magiclink');
            }
            $user = (object) [
                'auth' => 'magiclink',
                'confirmed' => 1,
                'mnethostid' => (int) $CFG->mnet_localhost_id,
                'username' => self::unique_username((string) $token->email),
                'password' => AUTH_PASSWORD_NOT_CACHED,
                'email' => (string) $token->email,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'maildisplay' => 0,
                'lang' => current_language(),
                'timezone' => '99',
            ];
            $user->id = user_api::create($user, false, true);
            $user = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        }
        $DB->set_field('auth_magiclink_token', 'timeused', time(), ['id' => $token->id]);
        return $user;
    }

    public static function safe_return_url(string $url): string {
        global $CFG;

        $url = trim($url);
        if ($url === '') {
            return '/';
        }
        $root = rtrim((string) $CFG->wwwroot, '/');
        if ($root !== '' && str_starts_with($url, $root)) {
            $url = substr($url, strlen($root));
            $url = $url === '' ? '/' : $url;
        }
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')
                || str_contains($url, '\\') || preg_match('/[\x00-\x1f\x7f]/', $url)) {
            return '/';
        }
        return $url;
    }

    /** Return non-deleted local accounts matching an email address without relying on database collation. */
    public static function users_by_email(string $email, string $fields = '*'): array {
        global $CFG, $DB;

        $emailequal = $DB->sql_equal('email', ':email', false);
        return $DB->get_records_select('user',
            "{$emailequal} AND mnethostid = :mnethostid AND deleted = 0", [
                'email' => trim($email),
                'mnethostid' => (int) $CFG->mnet_localhost_id,
            ], 'id ASC', $fields);
    }

    private static function unique_username(string $email): string {
        global $DB;
        $base = clean_param(strstr($email, '@', true) ?: 'customer', PARAM_USERNAME);
        $base = trim($base, '.-_') ?: 'customer';
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $suffix = $attempt === 0 ? '' : '-' . self::base64url(random_bytes(5));
            $username = \core_text::substr($base, 0, 80 - \core_text::strlen($suffix)) . $suffix;
            if (!$DB->record_exists('user', ['username' => $username])) {
                return $username;
            }
        }
        throw new \moodle_exception('usernamecollision', 'auth_magiclink');
    }

    private static function recipient(string $email): \stdClass {
        global $CFG;
        return (object) [
            'id' => -1, 'email' => $email, 'firstname' => $email, 'lastname' => '',
            'firstnamephonetic' => '', 'lastnamephonetic' => '', 'middlename' => '', 'alternatename' => '',
            'maildisplay' => 1, 'mailformat' => 1, 'maildigest' => 0, 'autosubscribe' => 1,
            'trackforums' => 0, 'deleted' => 0, 'suspended' => 0, 'auth' => 'magiclink',
            'mnethostid' => (int) $CFG->mnet_localhost_id,
        ];
    }

    private static function ttl(): int {
        $minutes = get_config('auth_magiclink', 'linklifetime');
        $minutes = $minutes === false ? 15 : (int) $minutes;
        return min(60, max(5, $minutes)) * MINSECS;
    }

    private static function account_creation_allowed(): bool {
        global $CFG;
        $configured = get_config('auth_magiclink', 'allownewaccounts');
        return empty($CFG->authpreventaccountcreation) && ($configured === false || !empty($configured));
    }

    private static function admin_links_allowed(): bool {
        return !empty(get_config('auth_magiclink', 'allowsiteadmins'));
    }

    private static function user_allowed(\stdClass $user): bool {
        return empty($user->suspended)
            && !empty($user->confirmed)
            && (string) $user->auth !== 'nologin'
            && !isguestuser($user)
            && (!is_siteadmin($user) || self::admin_links_allowed());
    }

    private static function base64url(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
