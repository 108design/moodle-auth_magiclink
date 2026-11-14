<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/mfa_bridge.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink\local;

defined('MOODLE_INTERNAL') || die();

/** Optional integration with Moodle's MFA email factor, never an overall MFA exemption. */
final class mfa_bridge {
    /** Clear authentication state belonging to the previous login, even when email credit is disabled. */
    public static function reset_session(): void {
        global $SESSION;

        foreach (array_keys(get_object_vars($SESSION)) as $name) {
            if (str_starts_with($name, 'factor_') || str_starts_with($name, 'tool_mfa_')
                    || str_starts_with($name, 'mfa_')) {
                unset($SESSION->$name);
            }
        }
    }

    /** Return the native trust duration only when this confirmation can offer device trust. */
    public static function trusted_device_duration(): int {
        if (!self::enabled() || !get_config('factor_email', 'enabled') || (int)get_config('factor_email', 'weight') <= 0
                || !get_config('factor_token', 'enabled')
                || (int)get_config('factor_token', 'weight') <= 0) {
            return 0;
        }
        return max(0, (int)get_config('factor_token', 'expiry'));
    }

    /**
     * Credit a consumed email link for the newly logged-in account. Core evaluates the complete policy afterwards.
     * This is called only after successful single-use token consumption and native login completion.
     */
    public static function confirm_email(\stdClass $user, string $verifiedemail, bool $trustdevice): bool {
        global $USER;

        if (!self::enabled() || (int)$USER->id !== (int)$user->id
                || \core_text::strtolower(trim($verifiedemail)) !== \core_text::strtolower(trim($USER->email))
                || !\tool_mfa\manager::is_ready()) {
            return false;
        }

        $factor = \tool_mfa\plugininfo\factor::get_factor('email');
        if (!$factor || !$factor->is_enabled() || $factor->get_weight() <= 0 || !$factor->has_input()) {
            return false;
        }
        $factor->load_locked_state();
        if (in_array($factor->get_state(), [\tool_mfa\plugininfo\factor::STATE_LOCKED,
                \tool_mfa\plugininfo\factor::STATE_FAIL], true)) {
            return false;
        }

        // Create the native factor record for normal audit/lastverified processing, without sending another email.
        $factor->get_active_user_factors($USER);
        if (!$factor->set_state(\tool_mfa\plugininfo\factor::STATE_PASS)) {
            return false;
        }

        if ($trustdevice && self::trusted_device_duration() > 0) {
            $tokenfactor = \tool_mfa\plugininfo\factor::get_factor('token');
            // Native post_pass_state creates the cookie only after the full MFA policy has passed.
            if ($tokenfactor) {
                $tokenfactor->global_submit((object)['factor_token_trust' => 1]);
            }
        }
        return true;
    }

    private static function enabled(): bool {
        return !empty(get_config('auth_magiclink', 'mfaemailconfirmation'))
            && class_exists(\tool_mfa\manager::class)
            && class_exists(\tool_mfa\plugininfo\factor::class)
            && !empty(get_config('tool_mfa', 'enabled'));
    }
}
