<?php

/**
 * PHPUnit bootstrap for wp-proud-agency.
 *
 * Load order:
 *   1. Patchwork, then Composer autoload — Patchwork must be in place first.
 *   2. Real wp-includes kses.php and formatting.php (see note below).
 *   3. stubs.php — WP stubs for everything else, all function_exists-guarded
 *      so nothing shadows the real functions loaded above.
 *   4. Plugin files under test.
 *
 * Run from the plugin root:
 *   composer install
 *   vendor/bin/phpunit
 */

require_once __DIR__ . '/../vendor/antecedent/patchwork/Patchwork.php';
require_once __DIR__ . '/../vendor/autoload.php';

$wpIncludes = __DIR__ . '/../../../../wp-includes/';

/*
 * The real escaping stack, not a model of it.
 *
 * These tests assert that editor-controlled meta cannot break out of an href
 * or inject markup, which means they are really asserting the behaviour of
 * esc_url(), wp_kses() and sanitize_email(). A hand-written stub of those is
 * worse than useless: wp-proud-core learned this in #1825 and recorded it in
 * its KsesFidelityTest — a modelled kses diverged from the real one on 22 of
 * 78 adversarial inputs, several in the dangerous direction, so a template
 * test passed while real WordPress emitted raw markup.
 *
 * kses.php and formatting.php both load standalone. Between them they need
 * WP_Token_Map and the HTML API (wp_kses_hair() parses attributes with it
 * since WP 7.0), plus wp_allowed_protocols() and apply_filters(), which
 * stubs.php and the AppliesPreKsesFilter trait supply.
 */
require_once $wpIncludes . 'class-wp-token-map.php';

foreach ([
    'class-wp-html-span.php',
    'class-wp-html-text-replacement.php',
    'html5-named-character-references.php',
    'class-wp-html-decoder.php',
    'class-wp-html-attribute-token.php',
    'class-wp-html-tag-processor.php',
] as $htmlApiFile) {
    $path = $wpIncludes . 'html-api/' . $htmlApiFile;
    if (!is_readable($path)) {
        fwrite(STDERR, "Cannot read {$path}.\nwp_kses() needs the HTML API to parse attributes.\n");
        exit(1);
    }
    require_once $path;
}

foreach (['compat.php', 'utf8.php', 'kses.php', 'formatting.php'] as $wpFile) {
    $path = $wpIncludes . $wpFile;
    if (!is_readable($path)) {
        fwrite(STDERR, "Cannot read {$path}.\nThese tests load the real escaping functions and must be run from inside a WordPress install.\n");
        exit(1);
    }
    require_once $path;
}

require_once __DIR__ . '/stubs.php';
require_once __DIR__ . '/AppliesPreKsesFilter.php';

// wp-proud-agency.php instantiates Proud\Agency\Agency at load (line 147) and
// declares the metabox classes behind class_exists('ProudMetaBox') guards.
// stubs.php supplies ProudPlugin and ProudMetaBox so both run as no-ops.
require_once __DIR__ . '/../wp-proud-agency.php';

// Widget classes under test. These extend Proud\Core\ProudWidget, stubbed in
// stubs.php rather than pulling in all of wp-proud-core.
require_once __DIR__ . '/../widgets/agency-social-links-widget.class.php';
require_once __DIR__ . '/../widgets/agency-contact-widget.class.php';
