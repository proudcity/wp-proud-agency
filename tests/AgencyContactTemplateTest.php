<?php

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

/**
 * Tests for widgets/templates/agency-contact.php, the template shared by the
 * AgencyContact and CustomContact widgets.
 *
 * This is the direct regression guard for issue #2933. Commit 9b60fdf rewrote
 * the template's social block to scan $instance for flat social_<service> keys
 * so CustomContact would work, but AgencyContact fed it a nested
 * $instance['social'] array. The keys never matched, $socialLinks was always
 * empty, and departments silently lost their social icons for seven months.
 */
class AgencyContactTemplateTest extends TestCase
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

    /**
     * Renders the shared contact template and returns its markup.
     *
     * Mirrors ProudWidget::printWidget(), which does extract($instance) and
     * then includes the template, so the template sees both the extracted
     * scalars and the full $instance array.
     *
     * @param array $instance Widget instance, as either widget expects it.
     */
    private function render(array $instance): string
    {
        $defaults = [
            'name'       => '',
            'name_title' => '',
            'name_link'  => '',
            'email'      => '',
            'phone'      => '',
            'fax'        => '',
            'sms'        => '',
            'address'    => '',
            'hours'      => '',
        ];
        $instance = array_merge($defaults, $instance);

        extract($instance);

        ob_start();
        include __DIR__ . '/../widgets/templates/agency-contact.php';
        return ob_get_clean();
    }

    // -----------------------------------------------------------------------
    // The #2933 regression
    // -----------------------------------------------------------------------

    /**
     * The exact shape AgencyContact::hasContent() now produces via
     * agency_social_meta(): flat keys alongside the nested array. This is what
     * renders San Rafael's Library and Recreation page.
     */
    public function test_renders_social_links_from_flat_keys(): void
    {
        $html = $this->render([
            'social_facebook'  => 'https://www.facebook.com/sanrafaellibraryrec/',
            'social_instagram' => 'https://www.instagram.com/sanrafaellibraryrec/',
            'social_youtube'   => 'https://www.youtube.com/@sanrafaellibraryrec',
            'social'           => [
                'facebook'  => 'https://www.facebook.com/sanrafaellibraryrec/',
                'instagram' => 'https://www.instagram.com/sanrafaellibraryrec/',
                'youtube'   => 'https://www.youtube.com/@sanrafaellibraryrec',
            ],
        ]);

        $this->assertStringContainsString('field-contact-social', $html);
        $this->assertStringContainsString('https://www.facebook.com/sanrafaellibraryrec/', $html);
        $this->assertStringContainsString('https://www.instagram.com/sanrafaellibraryrec/', $html);
        $this->assertStringContainsString('https://www.youtube.com/@sanrafaellibraryrec', $html);
        $this->assertSame(3, substr_count($html, '<li>'));
    }

    /**
     * The failing case as it existed between 9b60fdf and this fix: nested array
     * only, no flat keys. Kept so nobody "simplifies" agency_social_meta() back
     * down to one shape without also updating the template.
     */
    public function test_nested_only_instance_renders_nothing(): void
    {
        $html = $this->render([
            'social' => ['facebook' => 'https://www.facebook.com/sanrafaellibraryrec/'],
        ]);

        $this->assertStringNotContainsString('field-contact-social', $html);
    }

    /**
     * CustomContact puts social_* straight on the widget instance with no
     * nested array at all. It must keep working.
     */
    public function test_custom_contact_shape_still_renders(): void
    {
        $html = $this->render([
            'social_facebook' => 'https://facebook.com/somecity',
            'social_x'        => 'https://x.com/somecity',
        ]);

        $this->assertStringContainsString('field-contact-social', $html);
        $this->assertStringContainsString('fa-x-twitter', $html);
        $this->assertSame(2, substr_count($html, '<li>'));
    }

    // -----------------------------------------------------------------------
    // Emptiness
    // -----------------------------------------------------------------------

    public function test_no_social_block_when_no_accounts(): void
    {
        $html = $this->render(['name' => 'Library and Recreation']);

        $this->assertStringNotContainsString('field-contact-social', $html);
    }

    public function test_blank_values_produce_no_social_block(): void
    {
        $html = $this->render([
            'social_facebook' => '',
            'social_twitter'  => '   ',
        ]);

        $this->assertStringNotContainsString('field-contact-social', $html);
    }

    // -----------------------------------------------------------------------
    // Icons
    // -----------------------------------------------------------------------

    public function test_icon_classes_match_the_shared_helper(): void
    {
        foreach (array_keys(Proud\Agency\agency_social_services()) as $service) {
            $html = $this->render(['social_' . $service => 'https://example.com/acct']);

            $this->assertStringContainsString(
                'class="' . Proud\Agency\social_icon_classes($service) . '"',
                $html,
                "Template icon markup for '{$service}' does not match social_icon_classes()"
            );
        }
    }

    /**
     * A service not in the allowlist must be skipped outright rather than
     * rendered with a guessed icon class.
     */
    public function test_unknown_service_key_is_skipped(): void
    {
        $html = $this->render(['social_myspace' => 'https://myspace.com/sr']);

        $this->assertStringNotContainsString('field-contact-social', $html);
        $this->assertStringNotContainsString('myspace', $html);
    }

    /**
     * The loop only cares about the social_ prefix, so an unrelated key that
     * happens to start with it must not be treated as a service.
     */
    public function test_non_social_instance_keys_are_ignored(): void
    {
        $html = $this->render([
            'title'           => 'Contact',
            'social_security' => 'https://ssa.gov',
        ]);

        $this->assertStringNotContainsString('field-contact-social', $html);
    }

    // -----------------------------------------------------------------------
    // Escaping and scheme allowlisting
    // -----------------------------------------------------------------------

    /**
     * social_* meta is editor-controlled, so a quote must not be able to break
     * out of the href attribute.
     */
    public function test_url_is_escaped_in_href(): void
    {
        $html = $this->render([
            'social_facebook' => 'https://facebook.com/"><img src=x onerror=alert(1)>',
        ]);

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('onerror=alert(1)>', $html);
    }

    public function test_javascript_scheme_is_rejected(): void
    {
        $html = $this->render(['social_facebook' => 'javascript:alert(1)']);

        $this->assertStringNotContainsString('javascript:', $html);
    }

    /**
     * parse_url() returns null for an embedded newline, so the pre-filter lets
     * this through and esc_url() has to catch it.
     */
    public function test_obfuscated_javascript_scheme_is_rejected(): void
    {
        $html = $this->render(['social_facebook' => "java\nscript:alert(1)"]);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    public function test_data_uri_is_rejected(): void
    {
        $html = $this->render([
            'social_facebook' => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
        ]);

        $this->assertStringNotContainsString('data:text/html', $html);
    }

    /**
     * webcal is valid for iCal feeds and must survive.
     */
    public function test_webcal_allowed_for_ical_only(): void
    {
        $ical = $this->render(['social_ical' => 'webcal://example.com/feed.ics']);
        $this->assertStringContainsString('webcal://example.com/feed.ics', $ical);

        $facebook = $this->render(['social_facebook' => 'webcal://example.com/feed.ics']);
        $this->assertStringNotContainsString('webcal://', $facebook);
    }

    /**
     * Query strings must survive intact. Escaping at read time as well as
     * output time used to double-encode the ampersand.
     */
    public function test_query_string_is_not_double_encoded(): void
    {
        $html = $this->render([
            'social_youtube' => 'https://youtube.com/watch?v=abc&list=xyz',
        ]);

        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    /**
     * Outbound links in a new tab need noopener; this is an accessibility and
     * tabnabbing requirement the ProudCity templates hold to.
     */
    public function test_external_links_are_safe_and_labelled(): void
    {
        $html = $this->render(['social_facebook' => 'https://facebook.com/sr']);

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('title="Facebook"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }
}
