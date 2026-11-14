<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/form/settings_form.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */

namespace auth_magiclink\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/** Full-width Magic Link settings form. */
final class settings_form extends \moodleform {
    /** Define the form. */
    protected function definition(): void {
        $mform = $this->_form;
        $showcheckbox = $mform->createElement('advcheckbox', 'showonloginpage', '',
            get_string('showonloginpage_desc', 'auth_magiclink'));
        $mform->addGroup([$showcheckbox], 'showonloginpagegroup', get_string('showonloginpage', 'auth_magiclink'), '', false);
        $mform->setType('showonloginpage', PARAM_BOOL);
        $mform->addHelpButton('showonloginpagegroup', 'showonloginpage', 'auth_magiclink');
        $this->add_informed_checkbox('allownewaccounts');
        $mform->addElement('select', 'linklifetime', get_string('linklifetime', 'auth_magiclink'),
            [5 => '5', 10 => '10', 15 => '15', 30 => '30', 60 => '60']);
        $mform->setType('linklifetime', PARAM_INT);
        $mform->addHelpButton('linklifetime', 'linklifetime', 'auth_magiclink');
        $mform->addElement('static', 'linklifetimedescription', '',
            get_string('linklifetime_desc', 'auth_magiclink'));
        $this->add_informed_checkbox('allowsiteadmins');
        $this->add_informed_checkbox('mfaemailconfirmation');
        $mform->addElement('static', 'mfaadminrecommendation', '', get_string('mfaadminrecommendation', 'auth_magiclink'));
        $mform->hideIf('mfaadminrecommendation', 'mfaemailconfirmation', 'notchecked');
        $this->add_action_buttons(false);
    }

    /** Keep linked information after the native checkbox and its label, outside that label. */
    private function add_informed_checkbox(string $name): void {
        $mform = $this->_form;
        $checkbox = $mform->addElement('advcheckbox', $name, get_string($name, 'auth_magiclink'),
            get_string($name . '_desc', 'auth_magiclink'));
        // The shared form adapter places only the help node after the native checkbox label.
        $checkbox->setLabel($checkbox->getLabel() . ' ' . \auth_magiclink\local\settings_help::icon($name));
        $mform->setType($name, PARAM_BOOL);
    }
}
