<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/access_ui.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>.
// 108design source-available software license; see LICENSE.md.
namespace auth_magiclink\local;
defined('MOODLE_INTERNAL') || die();

/** The controller owns authentication; the theme or optional Frontpage owns presentation. */
final class access_ui {
    public static function page(callable $content): void {
        global $PAGE, $OUTPUT;
        $PAGE->set_pagelayout('login');
        $PAGE->set_cacheable(false);
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        if (class_exists('\\local_frontpage\\local\\login_surface')
                && method_exists('\\local_frontpage\\local\\login_surface', 'render_panel')
                && \local_frontpage\local\login_surface::render_panel('auth_magiclink', $content)) {
            return;
        }
        echo $OUTPUT->header();
        echo \html_writer::div($content(), 'login-container auth-magiclink-access-panel');
        echo $OUTPUT->footer();
    }
}
