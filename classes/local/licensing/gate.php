<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/licensing/gate.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace auth_magiclink\local\licensing;

defined('MOODLE_INTERNAL') || die();

/** Compatibility API for consumers; this distribution requires no activation. */
final class gate {
    public const FEATURE = 'auth_magiclink/runtime';

    public static function allows(): bool {
        return true;
    }

    public static function require(): void {
        // Authentication enablement and account/token policy are enforced by the login service.
    }
}
