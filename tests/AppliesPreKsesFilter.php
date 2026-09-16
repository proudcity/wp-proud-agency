<?php

use Brain\Monkey\Functions;

/**
 * Makes wp_kses() behave the way it does on a real site.
 *
 * bootstrap.php loads the real wp-includes/kses.php, but wp_kses() runs its
 * input through wp_kses_hook() first, which applies the `pre_kses` filter.
 * WordPress registers two callbacks there in default-filters.php:
 *
 *   wp_pre_kses_less_than        -- escapes a "<" that never becomes a tag
 *   wp_pre_kses_block_attributes -- no-op here; it only rewrites block markup
 *                                   when $allowed_html is a context string,
 *                                   and we always pass an array
 *
 * Without the first one, "<br" comes out of wp_kses() as a real <br> tag
 * instead of "&lt;br", so the tests would measure kses-without-its-filters --
 * a configuration no site actually runs.
 *
 * Brain Monkey defines apply_filters() itself, so the wiring has to go through
 * Brain Monkey rather than a function_exists() guard. Call from setUp() after
 * Monkey\setUp().
 *
 * Mirrors the trait of the same name in wp-proud-core/tests; see the note in
 * that plugin's KsesFidelityTest for why kses is loaded rather than modelled.
 */
trait AppliesPreKsesFilter
{
    protected function applyPreKsesFilter(): void
    {
        Functions\when('apply_filters')->alias(static function ($tag, $value = null) {
            if ('pre_kses' === $tag && function_exists('wp_pre_kses_less_than')) {
                return wp_pre_kses_less_than($value);
            }
            return $value;
        });
    }
}
