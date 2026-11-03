<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for auth.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/** General passwordless Moodle authentication through a short-lived verified email link. */
class auth_plugin_magiclink extends auth_plugin_base {
    public function __construct() {
        $this->authtype = 'magiclink';
        $this->config = get_config('auth_magiclink');
    }

    public function user_login($username, $password): bool {
        return false;
    }

    public function is_internal(): bool {
        return false;
    }

    public function can_change_password(): bool {
        return false;
    }

    public function prevent_local_passwords(): bool {
        return true;
    }

    public function can_be_manually_set(): bool {
        return true;
    }

    /** Present Magic Link as a passwordless method instead of an external identity provider. */
    public function loginpage_hook(): void {
        global $PAGE;

        if (!$this->show_on_login_page()) {
            return;
        }
        $PAGE->requires->js_call_amd('auth_magiclink/login_method', 'init', [[
            'path' => (new moodle_url('/auth/magiclink/login.php'))->get_path(),
            'heading' => get_string('loginmethodheading', 'auth_magiclink'),
            'description' => get_string('loginmethoddescription', 'auth_magiclink'),
        ]]);
    }

    /**
     * Keep Moodle's IDP-list API as the theme-independent, no-JavaScript fallback.
     *
     * The login-page module moves this entry into a semantically separate passwordless section. Extra metadata is
     * retained by current Moodle renderers and allows future themes to classify the method without parsing its URL.
     */
    public function loginpage_idp_list($wantsurl): array {
        if (!$this->show_on_login_page()) {
            return [];
        }
        $returnurl = \auth_magiclink\local\magic_link_service::safe_return_url((string) $wantsurl);
        return [[
            'url' => new moodle_url('/auth/magiclink/login.php', ['returnurl' => $returnurl]),
            'name' => get_string('loginbutton', 'auth_magiclink'),
            'authcomponent' => 'auth_magiclink',
            'loginmethod' => 'passwordless',
            'description' => get_string('loginmethoddescription', 'auth_magiclink'),
        ]];
    }

    /** Keep the verified gate separate from login-entry presentation. */
    protected function licence_allows_login(): bool {
        return \auth_magiclink\local\licensing\gate::allows();
    }

    private function show_on_login_page(): bool {
        return is_enabled_auth('magiclink')
            && (!isset($this->config->showonloginpage) || !empty($this->config->showonloginpage))
            && $this->licence_allows_login();
    }
}
