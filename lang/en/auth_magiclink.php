<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for lang/en/auth_magiclink.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
$string['pluginname'] = 'Magic Link Authentication';
$string['auth_description'] = 'General passwordless Moodle sign-in and optional account creation using a short-lived email link.';
$string['auth_magiclinkdescription'] = $string['auth_description'];
$string['passwordlesslogin'] = 'Sign in with a magic link';
$string['loginbutton'] = 'Get a magic link by email';
$string['loginmethodheading'] = 'Sign in without a password';
$string['loginmethoddescription'] = 'We will send you a single-use sign-in link by email.';
$string['loginintro'] = 'You will receive a single-use magic link. If account creation is enabled and you do not have an account yet, you only need to add your name.';
$string['sendlink'] = 'Send sign-in link';
$string['standardlogin'] = 'Sign in with your Moodle account';
$string['emailsent'] = 'If the address can be used, a sign-in link has been sent. Please also check your spam folder.';
$string['emailsubject'] = 'Your sign-in link for {$a}';
$string['emailbody'] = 'Open this link to sign in to {$a->site}:' . "\n\n"
    . '{$a->url}' . "\n\n"
    . 'The link is valid for {$a->minutes} minutes and can only be used once. Ignore this email if you did not request it.';
$string['completeaccount'] = 'Complete your account';
$string['completeaccountintro'] = 'The email address {$a} is confirmed. Only your first and last name are needed for your new account.';
$string['createaccountcontinue'] = 'Create account and continue';
$string['confirmsignin'] = 'Confirm sign-in';
$string['confirmsigninintro'] = 'Sign in as {$a}. The link is only used after you confirm.';
$string['signincontinue'] = 'Sign in and continue';
$string['tokeninvalid'] = 'This sign-in link is invalid, expired, or has already been used.';
$string['emailambiguous'] = 'This email address belongs to multiple accounts. Please contact support.';
$string['accountunavailable'] = 'This account cannot sign in with a magic link.';
$string['newaccountsdisabled'] = 'Account creation through magic links is currently unavailable.';
$string['namesrequired'] = 'First and last name are required.';
$string['usernamecollision'] = 'A unique account could not be created. Please try again.';
$string['taskcleanuptokens'] = 'Delete expired email sign-in links';
$string['showonloginpage'] = 'Show on the Moodle Login Page';
$string['showonloginpage_help'] = 'Adds Magic Link to the standard Moodle login page. The usual sign-in methods remain available. Enable Magic Link in Moodle authentication to offer this method.';
$string['showonloginpage_desc'] = 'Shows the magic-link entry point alongside the normal Moodle sign-in methods.';
$string['allownewaccounts'] = 'Allow New Accounts';
$string['allownewaccounts_help'] = 'Allows a new account with first and last name after email-link verification. Site-wide registration restrictions still apply.';
$string['allownewaccounts_desc'] = 'Creates a new passwordless Moodle account after the email address is confirmed and a first and last name are supplied.';
$string['allowsiteadmins'] = 'Allow Site Administrators';
$string['allowsiteadmins_desc'] = 'Allows site administrators to use Magic Link. Disabled by default for security; the configured MFA policy still applies.';
$string['allowsiteadmins_help'] = 'Allows website administrators to sign in with Magic Link. We recommend leaving this disabled, especially when Magic Link confirms the MFA email factor.';
$string['linklifetime'] = 'Link Lifetime';
$string['linklifetime_help'] = 'An unused link expires after this many minutes. It can be used only once and is consumed after the confirmation is submitted, not merely by opening the email link.';
$string['mfaemailconfirmation'] = 'Confirm the MFA Email Factor with Magic Link';
$string['mfaemailconfirmation_desc'] = 'Counts a confirmed Magic Link as the email factor so that Moodle does not send a second email code.';
$string['mfaemailconfirmation_help'] = 'A consumed Magic Link also confirms the email factor, avoiding a second email code. Both proofs rely on the same mailbox; other MFA requirements still apply.';
$string['mfaadminrecommendation'] = 'Recommendation: leave Allow Site Administrators disabled when using a single email. Protect administrator sign-in with a password and an authenticator app or security key.';
$string['settingshelp'] = 'Magic Link Help';
$string['settingshelpmore'] = 'More in the help panel';
$string['linklifetime_desc'] = 'Time in minutes before an unused magic link expires.';
$string['privacy:metadata:auth_magiclink_token'] = 'Short-lived magic links and the email address required for them.';

$string['authdisabled'] = 'Magic Link sign-in is disabled on this site. Use another sign-in method.';

$string['checkmailbox'] = 'Check your inbox and spam folder. Open the link in the email to continue.';
$string['useanotheremail'] = 'Use another email address';

$string['linkunavailable'] = 'This sign-in link has expired or cannot be used. Please request a new link.';
$string['requestnewlink'] = 'Request a new sign-in link';

$string['settingshelptopic'] = 'Topic';

$string['settingshelp_overview'] = 'Using Magic Link';

$string['settingshelp_overview_article'] = '<p>Magic Link provides sign-in through a single-use email link. Enable Magic Link in Moodle authentication to offer this method. Show on the Moodle Login Page adds its entry to the standard login page.</p><p>A person requests a link for their email address and confirms sign-in on the linked page. Merely opening the link does not consume it; submitting confirmation does. An unused link expires after the selected Link Lifetime.</p><p>Existing accounts retain their current authentication method. Standard Moodle sign-in methods remain available. A permitted local return destination is retained after successful sign-in.</p>';

$string['settingshelp_allownewaccounts'] = 'Allow New Accounts';

$string['settingshelp_allownewaccounts_article'] = '<p>When Allow New Accounts is enabled, a person without an existing account can enter their first and last name after verifying their email address. The new passwordless account is created only then. Site-wide registration restrictions remain authoritative.</p><p>New accounts use Magic Link authentication and receive no local password through this flow. Existing confirmed accounts retain their current authentication method; this option does not convert them.</p><p>Disable the option when only previously created accounts should use this alternative sign-in method. Standard Moodle sign-in methods retain their own settings.</p>';

$string['settingshelp_allowsiteadmins'] = 'Allow Site Administrators';

$string['settingshelp_allowsiteadmins_article'] = '<p>This option concerns website administrators. It grants no administrator permissions and replaces no MFA policy. When disabled, website administrators cannot use Magic Link to sign in.</p><p>We recommend leaving it disabled and retaining a working administrator sign-in using a password and an authenticator app or security key. This is particularly relevant when Confirm the MFA Email Factor with Magic Link is enabled: mailbox access then supplies both the Magic Link and the additional email confirmation.</p><p>Configure the general MFA policy separately in site administration. This Magic Link setting neither enables nor disables MFA and does not enroll an app or security key.</p>';

$string['settingshelp_mfaemailconfirmation'] = 'Confirm the MFA Email Factor with Magic Link';

$string['settingshelp_mfaemailconfirmation_article'] = '<p>This requires Moodle MFA and an active email factor with positive weight. After a single-use Magic Link is successfully consumed, only the native email factor for the account actually signed in is confirmed. Moodle does not need to send a second email code for that factor.</p><p>The configured weight remains effective. If it does not satisfy the entire MFA policy, Moodle requests the remaining factors. Revoked, locked or failed email factors are not unlocked by this option. Without active Moodle MFA, ordinary Magic Link sign-in continues to work.</p><p>This combination reduces sign-in friction but does not provide an independent second proof against mailbox compromise. An authenticator app or security key is a stronger addition for sensitive accounts. We therefore recommend leaving Allow Site Administrators disabled.</p><p>Successful Magic Link account switching clears previous MFA session state. Invalid, expired or already consumed links cannot sign in or produce new MFA confirmation.</p>';

$string['settingshelp_trusteddevices'] = 'Trusted Devices';

$string['settingshelp_trusteddevices_article'] = '<p>When email-factor confirmation and the native trusted-device factor are enabled, the Magic Link confirmation can offer an optional device choice. It is not selected by default.</p><p>Moodle creates the native device proof only after the entire MFA policy succeeds. Duration and overnight expiry follow the Moodle factor settings; Magic Link maintains no separate trust list.</p><p>Use this choice only on a device you control. Leave it unselected on shared or public devices.</p>';

$string['contexthelptopics'] = 'Help Topics';

