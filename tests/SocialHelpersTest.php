<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the shared social helpers in wp-proud-agency.php:
 * social_icon_classes(), social_allowed_schemes() and agency_social_meta().
 *
 * These were extracted while fixing issue #2933. The icon map previously lived
 * inline in widgets/templates/agency-contact.php while a second, stale copy
 * lived in the AgencySocial widget; the two drifted and the widget stopped
 * rendering. Keeping them covered here is what stops that recurring.
 */
class SocialHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // social_icon_classes()
    // -----------------------------------------------------------------------

    /**
     * Every service the plugin offers in the admin must map to an icon.
     * A service that can be entered but cannot be rendered is exactly the
     * class of bug #2933 was.
     */
    public function test_every_offered_service_has_an_icon(): void
    {
        foreach (array_keys(Proud\Agency\agency_social_services()) as $service) {
            $this->assertNotNull(
                Proud\Agency\social_icon_classes($service),
                "Service '{$service}' is offered in the admin but has no icon mapping"
            );
        }
    }

    /**
     * Brand-name services use the Font Awesome 6 brands style; the three
     * generic ones are solid icons and would render as blank boxes under
     * fa-brands.
     */
    public function test_brand_services_use_fa_brands(): void
    {
        $this->assertSame('fa fa-brands fa-square-facebook', Proud\Agency\social_icon_classes('facebook'));
        $this->assertSame('fa fa-brands fa-square-youtube', Proud\Agency\social_icon_classes('youtube'));
        $this->assertSame('fa fa-brands fa-square-snapchat', Proud\Agency\social_icon_classes('snapchat'));
    }

    public function test_generic_services_use_fa_solid(): void
    {
        $this->assertSame('fa fa-solid fa-square-rss', Proud\Agency\social_icon_classes('rss'));
        $this->assertSame('fa fa-solid fa-calendar', Proud\Agency\social_icon_classes('ical'));
        $this->assertSame('fa fa-solid fa-door-open', Proud\Agency\social_icon_classes('nextdoor'));
    }

    /**
     * X and Twitter are separate fields; X must not fall back to the bird.
     */
    public function test_x_maps_to_x_twitter_not_twitter(): void
    {
        $this->assertSame('fa fa-brands fa-x-twitter', Proud\Agency\social_icon_classes('x'));
        $this->assertSame('fa fa-brands fa-square-twitter', Proud\Agency\social_icon_classes('twitter'));
    }

    /**
     * Services with no square-* variant in FA6 must not request one.
     */
    public function test_services_without_square_variant_are_not_squared(): void
    {
        $this->assertStringNotContainsString('square', Proud\Agency\social_icon_classes('instagram'));
        $this->assertStringNotContainsString('square', Proud\Agency\social_icon_classes('tiktok'));
        $this->assertStringNotContainsString('square', Proud\Agency\social_icon_classes('x'));
    }

    /**
     * Doubles as the render allowlist: null means "skip", so an unknown key can
     * never reach a class attribute.
     */
    public function test_unknown_service_returns_null(): void
    {
        $this->assertNull(Proud\Agency\social_icon_classes('myspace'));
        $this->assertNull(Proud\Agency\social_icon_classes(''));
        $this->assertNull(Proud\Agency\social_icon_classes('"><script>alert(1)</script>'));
    }

    public function test_non_string_service_returns_null(): void
    {
        $this->assertNull(Proud\Agency\social_icon_classes(0));
        $this->assertNull(Proud\Agency\social_icon_classes(null));
        $this->assertNull(Proud\Agency\social_icon_classes([]));
    }

    /**
     * The return value is built only from hardcoded literals, so no caller
     * input can ever reach the class attribute.
     */
    public function test_service_name_never_appears_in_output(): void
    {
        foreach (array_keys(Proud\Agency\agency_social_services()) as $service) {
            $classes = Proud\Agency\social_icon_classes($service);
            $this->assertMatchesRegularExpression('/^fa fa-(brands|solid) fa-[a-z-]+$/', $classes);
        }
    }

    // -----------------------------------------------------------------------
    // social_allowed_schemes()
    // -----------------------------------------------------------------------

    public function test_default_schemes_are_http_only(): void
    {
        $this->assertSame(['http', 'https'], Proud\Agency\social_allowed_schemes('facebook'));
    }

    /**
     * iCal feeds are commonly published as webcal:// links.
     */
    public function test_ical_additionally_allows_webcal(): void
    {
        $this->assertSame(['http', 'https', 'webcal'], Proud\Agency\social_allowed_schemes('ical'));
    }

    /**
     * No service may ever permit a script-bearing scheme.
     */
    public function test_no_service_allows_javascript_or_data(): void
    {
        foreach (array_keys(Proud\Agency\agency_social_services()) as $service) {
            $schemes = Proud\Agency\social_allowed_schemes($service);
            $this->assertNotContains('javascript', $schemes);
            $this->assertNotContains('data', $schemes);
        }
    }

    // -----------------------------------------------------------------------
    // agency_social_meta()
    // -----------------------------------------------------------------------

    /**
     * Stubs get_post_meta for a single post's social_* keys.
     *
     * @param array $map service key => stored value
     */
    private function stubSocialMeta(array $map): void
    {
        Functions\when('get_post_meta')->alias(function ($id, $key, $single = false) use ($map) {
            $service = str_starts_with($key, 'social_') ? substr($key, 7) : null;
            return $service !== null && isset($map[$service]) ? $map[$service] : '';
        });
    }

    /**
     * The regression behind #2933: the contact template scans $instance for
     * flat social_<service> keys, so returning only the nested array renders
     * nothing. Both shapes must come back.
     */
    public function test_returns_both_flat_and_nested_shapes(): void
    {
        $this->stubSocialMeta(['facebook' => 'https://facebook.com/sr']);

        $out = Proud\Agency\agency_social_meta(123);

        $this->assertSame('https://facebook.com/sr', $out['social_facebook'], 'flat key missing');
        $this->assertSame('https://facebook.com/sr', $out['social']['facebook'], 'nested key missing');
    }

    /**
     * hasContent() decides whether to render at all from the nested array, so
     * it must always exist even with nothing stored.
     */
    public function test_nested_key_always_present_even_when_empty(): void
    {
        $this->stubSocialMeta([]);

        $out = Proud\Agency\agency_social_meta(123);

        $this->assertSame(['social' => []], $out);
        $this->assertEmpty($out['social']);
    }

    /**
     * Departments fill in a few accounts and leave the rest blank. Blank keys
     * must not produce empty icons.
     */
    public function test_empty_and_whitespace_values_are_skipped(): void
    {
        $this->stubSocialMeta([
            'facebook'  => 'https://facebook.com/sr',
            'twitter'   => '',
            'instagram' => '   ',
            'youtube'   => "\n\t",
        ]);

        $out = Proud\Agency\agency_social_meta(123);

        $this->assertSame(['facebook' => 'https://facebook.com/sr'], $out['social']);
        $this->assertArrayNotHasKey('social_twitter', $out);
        $this->assertArrayNotHasKey('social_instagram', $out);
        $this->assertArrayNotHasKey('social_youtube', $out);
    }

    public function test_surrounding_whitespace_is_trimmed(): void
    {
        $this->stubSocialMeta(['facebook' => '  https://facebook.com/sr  ']);

        $out = Proud\Agency\agency_social_meta(123);

        $this->assertSame('https://facebook.com/sr', $out['social_facebook']);
    }

    /**
     * Escaping belongs at output, not at read time. The pre-#2933 code ran
     * esc_html() here, which double-encoded query strings once the template
     * started calling esc_url() as well.
     */
    public function test_values_are_returned_raw_for_output_time_escaping(): void
    {
        $this->stubSocialMeta(['facebook' => 'https://facebook.com/p?a=1&b=2']);

        $out = Proud\Agency\agency_social_meta(123);

        $this->assertSame('https://facebook.com/p?a=1&b=2', $out['social_facebook']);
        $this->assertStringNotContainsString('&amp;', $out['social_facebook']);
    }

    /**
     * Only keys the plugin actually offers are read, so stray social_* meta
     * written by anything else never reaches a render path.
     */
    public function test_only_known_services_are_read(): void
    {
        Functions\when('get_post_meta')->alias(function ($id, $key, $single = false) {
            return $key === 'social_myspace' ? 'https://myspace.com/sr' : '';
        });

        $out = Proud\Agency\agency_social_meta(123);

        $this->assertSame(['social' => []], $out);
    }
}
