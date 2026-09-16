<?php

/**
 * Minimal WordPress stubs for wp-proud-agency testing.
 *
 * Covers calls made at file-include time plus the handful of functions the
 * real kses.php / formatting.php need at call time. Everything here is
 * function_exists-guarded, so nothing shadows the real WordPress functions
 * bootstrap.php loads first.
 *
 * Deliberately absent: esc_url(), esc_html(), esc_attr(), wp_kses(),
 * sanitize_email(), is_email(). Those are the functions under test and come
 * from real WordPress — see the note in bootstrap.php.
 */

namespace {
    if (!function_exists('add_action')) {
        function add_action() { return true; }
    }
    if (!function_exists('add_filter')) {
        function add_filter() { return true; }
    }
    if (!function_exists('register_widget')) {
        function register_widget() { return true; }
    }
    if (!function_exists('is_admin')) {
        function is_admin() { return false; }
    }
    if (!function_exists('plugin_dir_path')) {
        function plugin_dir_path($file) { return dirname($file) . '/'; }
    }
    if (!function_exists('__')) {
        function __($text, $domain = null) { return $text; }
    }
    if (!function_exists('_deprecated_function')) {
        function _deprecated_function() {}
    }
    if (!function_exists('_wp_can_use_pcre_u')) {
        function _wp_can_use_pcre_u() { return true; }
    }
    if (!function_exists('wp_parse_url')) {
        function wp_parse_url($url, $component = -1) {
            return parse_url($url, $component);
        }
    }

    /**
     * esc_attr()/esc_html() reach _wp_specialchars(), which asks whether the
     * site charset is UTF-8. Every ProudCity install is, and the real
     * is_utf8_charset() lives in functions.php, which does not load standalone.
     */
    if (!function_exists('is_utf8_charset')) {
        function is_utf8_charset($blog_charset = null) {
            // Verbatim from functions.php, with the site charset fixed at UTF-8.
            return _is_utf8_charset($blog_charset ?? 'UTF-8');
        }
    }
    if (!function_exists('get_option')) {
        function get_option($option, $default = false) {
            return 'blog_charset' === $option ? 'UTF-8' : $default;
        }
    }

    // Needed by esc_url() and wp_kses_bad_protocol(). A full WordPress load
    // would provide this; kses.php and formatting.php do not define it.
    if (!function_exists('wp_allowed_protocols')) {
        function wp_allowed_protocols() {
            return [
                'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs',
                'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel',
                'fax', 'xmpp', 'webcal', 'urn',
            ];
        }
    }

    /**
     * Stand-in for the wp-proud-core base plugin class. wp-proud-agency.php
     * does `$Agency = new Agency()` at load; the constructor only registers
     * hooks, so a no-op base is enough.
     */
    if (!class_exists('ProudPlugin')) {
        class ProudPlugin {
            public function __construct($args = []) {}
            public function hook() {}
        }
    }

    /**
     * Stand-in for the metabox base. Declared so the class_exists() guards in
     * wp-proud-agency.php are true and the metabox classes get defined; the
     * `new` calls behind them are skipped because is_admin() stubs to false.
     */
    if (!class_exists('ProudMetaBox')) {
        class ProudMetaBox {
            public $options = [];
            public $fields  = [];
            public function __construct($key = '', $title = '', $screen = '', $position = '', $priority = '') {}
        }
    }
}

namespace Proud\Core {
    /**
     * Stand-in for the ProudWidget base so the agency widget classes can be
     * loaded without all of wp-proud-core. Only the constructor signature and
     * the members the agency widgets touch are reproduced.
     */
    if (!class_exists('Proud\Core\ProudWidget')) {
        class ProudWidget {
            public $id_base;
            public $name;
            public $settings = [];
            public function __construct($id_base = '', $name = '', $widget_options = []) {
                $this->id_base = $id_base;
                $this->name    = $name;
                if (method_exists($this, 'initialize')) {
                    $this->initialize();
                }
            }
        }
    }
}
