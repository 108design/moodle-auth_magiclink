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

/** Creates and consumes single-use email links without exposing account existence. */
final class magic_link_service {
    private const DEFAULT_TTL = 900;
    private const MAX_REQUESTS_PER_HOUR = 8;

    /** Apply Moodle's provider switch at every entry point, including already issued links. */
    public static function require_enabled(): void {
        if (!is_enabled_auth('magiclink')) {
            throw new \moodle_exception('authdisabled', 'auth_magiclink');
        }
    }

    public static function request(string $email, string $returnurl, string $remoteaddress): void {
        global $CFG, $DB, $SITE;

        self::require_enabled();
        $email = \core_text::strtolower(trim($email));
        if (!validate_email($email) || \core_text::strlen($email) > 100) {
            throw new \moodle_exception('invalidemail');
        }
        $returnurl = self::safe_return_url($returnurl);
        $users = self::users_by_email($email);
        if (count($users) > 1) {
            return;
        }
        if ($users) {
            $user = reset($users);
            if (!self::user_allowed($user)) {
                return;
            }
        } else if (!self::account_creation_allowed() || email_is_not_allowed($email)) {
            return;
        }

        $iphash = hash('sha256', "auth_magiclink\0" . $remoteaddress);
        $recent = $DB->count_records_select('auth_magiclink_token',
            '(email = :email OR requestiphash = :iphash) AND timecreated >= :since', [
                'email' => $email,
                'iphash' => $iphash,
                'since' => time() - HOURSECS,
            ]);
        if ($recent >= self::MAX_REQUESTS_PER_HOUR) {
            return;
        }
        $token = self::base64url(random_bytes(32));
        $now = time();
        $tokenid = $DB->insert_record('auth_magiclink_token', (object) [
            'tokenhash' => hash('sha256', $token),
            'email' => $email,
            'returnurl' => $returnurl,
            'requestiphash' => $iphash,
            'timeexpires' => $now + self::ttl(),
            'timecreated' => $now,
        ]);
        $url = new \moodle_url('/auth/magiclink/confirm.php', ['token' => $token]);
        $recipient = self::recipient($email);
        $subject = get_string('emailsubject', 'auth_magiclink', format_string($SITE->shortname));
        $text = get_string('emailbody', 'auth_magiclink', [
            'site' => format_string($SITE->fullname),
            'url' => $url->out(false),
            'minutes' => (int) (self::ttl() / MINSECS),
        ]);
        $sent = false;
        try {
            $sent = email_to_user($recipient, \core_user::get_noreply_user(), $subject, $text,
                str_replace(s($url->out(false)), \html_writer::link($url, s($url->out(false))),
                    text_to_html(s($text), false, false, true)));
        } finally {
            if (!$sent) {
                // Keep the request for rate limiting, but never accept an undelivered login token.
                $DB->set_field('auth_magiclink_token', 'timeused', time(), ['id' => $tokenid]);
            }
        }
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
        if (!empty($token->timeused) || (int) $token->timeexpires < time()) {
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
            $user->id = user_api::create($user, false, false);
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
