<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/settings_help.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen
 * @license   108design source-available software license; see LICENSE.md
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
namespace auth_magiclink\local;

defined('MOODLE_INTERNAL') || die();

/** Contextual settings help, following the standalone Frontpage panel contract. */
final class settings_help {
    private const TOPICS = ['overview', 'allownewaccounts', 'allowsiteadmins', 'mfaemailconfirmation', 'trusteddevices'];
    private static bool $prepared = false;

    /** Register only the permitted settings page before its header is rendered. */
    public static function prepare(): void {
        global $CFG, $PAGE;
        self::$prepared = true;
        $PAGE->requires->css(new \moodle_url('/auth/magiclink/styles/context_help.css', [
            'rev' => get_config('auth_magiclink', 'version'),
        ]));
        $module = (int)$CFG->version < 2025041400 ? 'context_help_legacy'
            : ((int)$CFG->version >= 2026100500 ? 'context_help_53' : 'context_help');
        $PAGE->requires->js_call_amd('auth_magiclink/context_help_form', 'init');
        $PAGE->requires->js_call_amd('auth_magiclink/' . $module, 'init', [[
            'topic' => optional_param('magiclinkhelp', '', PARAM_ALPHA),
        ]]);
    }

    /** Native popover with concise guidance and a direct link to the extended topic. */
    public static function icon(string $key): string {
        global $CFG, $PAGE;
        if (!in_array($key, self::TOPICS, true)) {
            throw new \coding_exception('Unknown Magic Link help topic.');
        }
        $url = new \moodle_url($PAGE->url);
        $url->param('magiclinkhelp', $key);
        $help = new \help_icon($key, 'auth_magiclink', $url->out(false));
        $renderer = $PAGE->get_renderer('core');
        $data = (array)$help->export_for_template($renderer);
        $data['completedoclink'] = \html_writer::link($url, get_string('settingshelpmore', 'auth_magiclink'));
        $data['legacy'] = (int)$CFG->version < 2025041400;
        $data['infoicon'] = (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo';
        return $renderer->render_from_template('auth_magiclink/settings_help_icon', $data);
    }

    /** Always available in the native Topnav user area on the registered page. */
    public static function navbar(\renderer_base $renderer): string {
        global $CFG;
        if (!self::$prepared) {
            return '';
        }
        $label = get_string('settingshelp', 'auth_magiclink');
        return \html_writer::tag('button', $renderer->pix_icon(
            (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo', ''), [
                'type' => 'button', 'class' => 'nav-link icon-no-margin auth-magiclink-help-navbar',
                'data-magiclink-help-open' => '', 'aria-label' => $label, 'title' => $label,
                'aria-controls' => 'auth-magiclink-context-help', 'aria-expanded' => 'false',
            ]);
    }

    /** Same header, topic chooser and article layout as the Frontpage reference. */
    public static function render(): string {
        global $PAGE;
        $title = get_string('settingshelp', 'auth_magiclink');
        $close = \html_writer::tag('button', \html_writer::span('&#215;', '', ['aria-hidden' => 'true']), [
            'type' => 'button', 'class' => 'btn btn-secondary btn-sm auth-magiclink-help-close',
            'aria-label' => get_string('closebuttontitle', 'moodle'), 'data-magiclink-help-close' => '1',
        ]);
        $options = '';
        $articles = '';
        foreach (self::TOPICS as $topic) {
            $label = get_string('settingshelp_' . $topic, 'auth_magiclink');
            $options .= \html_writer::tag('option', $label, ['value' => $topic]);
            $articles .= \html_writer::tag('article', \html_writer::tag('h3', $label, ['class' => 'h5', 'tabindex' => '-1'])
                . get_string('settingshelp_' . $topic . '_article', 'auth_magiclink'), [
                    'data-magiclink-help-article' => $topic, 'hidden' => 'hidden',
                ]);
        }
        $choice = \html_writer::tag('label', get_string('contexthelptopics', 'auth_magiclink'), [
            'for' => 'auth-magiclink-help-topic', 'class' => 'form-label',
        ]) . \html_writer::tag('select', $options, [
            'id' => 'auth-magiclink-help-topic', 'class' => 'form-select custom-select',
        ]);
        // Shared help-panel identity: no link or action; close remains beside the topic selector.
        $brand = \html_writer::tag('span', \html_writer::empty_tag('img', [
            'src' => $PAGE->get_renderer('core')->image_url('helpbrand', 'auth_magiclink')->out(false),
            'alt' => '', 'width' => 25, 'height' => 24,
        ]), [
            'class' => 'auth-magiclink-help-brand', 'role' => 'img', 'aria-label' => '108design',
            'title' => '108design', 'tabindex' => '0', 'data-toggle' => 'tooltip', 'data-bs-toggle' => 'tooltip',
        ]);
        $header = \html_writer::tag('header', \html_writer::tag('h2', $title, [
            'id' => 'auth-magiclink-context-help-title', 'class' => 'sr-only visually-hidden', 'tabindex' => '-1',
        ]) . \html_writer::div($choice, 'auth-magiclink-help-choices') . $brand . $close,
            ['class' => 'auth-magiclink-help-header auth-magiclink-help-header-branded']);
        return \html_writer::tag('aside', $header
            . \html_writer::div($articles, 'auth-magiclink-help-content', ['tabindex' => '0']), [
                'id' => 'auth-magiclink-context-help', 'class' => 'auth-magiclink-context-help',
                'aria-labelledby' => 'auth-magiclink-context-help-title', 'hidden' => 'hidden',
                'data-magiclink-help-url' => $PAGE->url->out(false),
            ]);
    }
}
