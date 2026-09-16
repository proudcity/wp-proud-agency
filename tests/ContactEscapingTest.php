<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Escaping tests for the agency contact output paths.
 *
 * These cover two stored-XSS findings raised in the security review of #2933.
 * Both are older than that issue but live in the code it touches:
 *
 *   1. $name_link was interpolated raw into an href. AgencyContact "validated"
 *      it with filter_var(FILTER_VALIDATE_EMAIL), which accepts RFC quoted
 *      local parts, so a payload passed validation and skipped esc_url()
 *      entirely. CustomContact fed the same field with no escaping at all.
 *
 *   2. phone_tel_links() and email_mailto_links() linkify with preg_replace()
 *      and return the whole string -- non-matching content included -- as raw
 *      HTML, which the template prints unescaped.
 *
 * The fix cannot simply esc_html() the input. A real stored value on San
 * Rafael's Police department is:
 *
 *   Emergencies: 9-1-1   <br>  Main: (415) 485-3304
 *
 * so the <br> has to survive. That is the same allowlist wp-proud-core settled
 * on for widget titles in #2916: wp_kses($value, ['br' => []]).
 */
class ContactEscapingTest extends TestCase
{
    use AppliesPreKsesFilter;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->applyPreKsesFilter();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // phone_tel_links()
    // -----------------------------------------------------------------------

    /**
     * The real San Rafael Police value. Escaping must not turn this <br> into
     * visible text on the front end.
     */
    public function test_preserves_the_br_editors_actually_use(): void
    {
        $out = Proud\Agency\AgencyContact::phone_tel_links('Emergencies: 9-1-1   <br>  Main: (415) 485-3304 ');

        $this->assertStringContainsString('<br>', $out);
        $this->assertStringNotContainsString('&lt;br', $out);
        $this->assertStringContainsString('tel:(415) 485-3304', $out);
    }

    public function test_linkifies_a_plain_number(): void
    {
        $out = Proud\Agency\AgencyContact::phone_tel_links('(415) 485-3333');

        $this->assertStringContainsString('href="tel:(415) 485-3333"', $out);
        $this->assertStringContainsString('title="Call this number"', $out);
    }

    /**
     * Comma-separated numbers are split onto their own lines; a real stored
     * value relies on this.
     */
    public function test_commas_become_line_breaks(): void
    {
        $out = Proud\Agency\AgencyContact::phone_tel_links('Recreation: (415) 485-3333, Library: (415) 485-3323');

        $this->assertStringContainsString('<br/>', $out);
        $this->assertStringContainsString('tel:(415) 485-3333', $out);
        $this->assertStringContainsString('tel:(415) 485-3323', $out);
    }

    public function test_script_tag_in_phone_is_neutralised(): void
    {
        $out = Proud\Agency\AgencyContact::phone_tel_links('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', $out);
    }

    /**
     * The payload from the security review.
     */
    public function test_img_onerror_in_phone_is_neutralised(): void
    {
        $out = Proud\Agency\AgencyContact::phone_tel_links('<img src=x onerror=alert(document.cookie)>');

        $this->assertStringNotContainsString('<img', $out);
        $this->assertStringNotContainsString('onerror', $out);
    }

    /**
     * An allowed tag must not keep a disallowed attribute.
     */
    public function test_event_handler_on_allowed_tag_is_stripped(): void
    {
        $out = Proud\Agency\AgencyContact::phone_tel_links('<br onmouseover=alert(1)>');

        $this->assertStringNotContainsString('onmouseover', $out);
    }

    public function test_sms_prefix_is_honoured(): void
    {
        $out = Proud\Agency\AgencyContact::phone_tel_links('(415) 485-3333', 'sms');

        $this->assertStringContainsString('href="sms:(415) 485-3333"', $out);
    }

    // -----------------------------------------------------------------------
    // email_mailto_links()
    // -----------------------------------------------------------------------

    public function test_linkifies_an_email(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('library@example.gov');

        $this->assertStringContainsString('href="mailto:library@example.gov"', $out);
        $this->assertStringContainsString('title="Send email"', $out);
    }

    /**
     * Most departments store a contact-page URL in this field rather than an
     * address; the template picks a different icon for it.
     */
    public function test_linkifies_a_contact_page_url(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('https://example.gov/contact-parking/');

        $this->assertStringContainsString('href="https://example.gov/contact-parking/"', $out);
        $this->assertStringContainsString('Contact us', $out);
    }

    public function test_script_tag_in_email_is_neutralised(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', $out);
    }

    public function test_img_onerror_in_email_is_neutralised(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('<img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<img', $out);
        $this->assertStringNotContainsString('onerror', $out);
    }

    /**
     * The captured URL lands in an href, so a quote in it must not be able to
     * open a second attribute.
     */
    public function test_quote_in_url_cannot_break_out_of_href(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('https://example.gov/"onmouseover="alert(1)');

        $this->assertStringNotContainsString('onmouseover="alert(1)"', $out);
        $this->assertStringNotContainsString('" onmouseover', $out);
    }

    public function test_javascript_url_is_not_linkified(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('javascript:alert(1)');

        $this->assertStringNotContainsString('href="javascript:', $out);
    }

    // -----------------------------------------------------------------------
    // Output corruption (single-pass linkifying)
    // -----------------------------------------------------------------------

    /**
     * The URL host pattern does not consume "@", so when the email pass ran as
     * a separate preg_replace() it re-matched the URL pass's own output and the
     * anchor came out mangled: <a <a href="mailto:href=https//...">.
     */
    public function test_url_containing_an_at_sign_is_not_re_linkified(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('https://city.gov/contact?to=a@b.com');

        $this->assertSame('<a href="https://city.gov/contact?to=a@b.com">Contact us</a>', $out);
        $this->assertSame(1, substr_count($out, '<a '));
    }

    public function test_url_with_userinfo_is_not_re_linkified(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('https://user@city.gov/contact');

        $this->assertStringNotContainsString('mailto:', $out);
        $this->assertSame(1, substr_count($out, '<a '));
    }

    /**
     * The comma-to-<br/> substitution used to run AFTER linkifying, so a comma
     * inside a URL landed inside the generated href.
     */
    public function test_comma_never_lands_inside_an_href(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('https://city.gov/a,b/contact');

        $this->assertStringNotContainsString('<br/>b/contact"', $out);
        $this->assertSame(1, preg_match('/<a href="([^"]*)"/', $out, $m));
        $this->assertStringNotContainsString('<br', $m[1]);
    }

    public function test_multiple_emails_each_linkify(): void
    {
        $out = Proud\Agency\AgencyContact::email_mailto_links('a@b.gov, c@d.gov');

        $this->assertSame(2, substr_count($out, '<a '));
        $this->assertStringContainsString('mailto:a@b.gov', $out);
        $this->assertStringContainsString('mailto:c@d.gov', $out);
        $this->assertStringContainsString('<br/>', $out);
    }

    /**
     * Generated markup must never be re-scanned, whichever field it came from.
     */
    public function test_phone_output_contains_exactly_one_anchor_per_number(): void
    {
        $out = Proud\Agency\AgencyContact::phone_tel_links('Recreation: (415) 485-3333, Library: (415) 485-3323');

        $this->assertSame(2, substr_count($out, '<a '));
        $this->assertSame(2, substr_count($out, '</a>'));
    }

    // -----------------------------------------------------------------------
    // name_link
    // -----------------------------------------------------------------------

    /**
     * The FILTER_VALIDATE_EMAIL bypass. PHP accepts an RFC quoted local part,
     * so this payload validates as an email and, before the fix, was
     * concatenated into "mailto:..." and printed with no escaping at all.
     */
    public function test_quoted_local_part_payload_cannot_reach_the_href(): void
    {
        $payload = '"><img/src/onerror=alert(1)>"@a.com';

        $this->assertNotFalse(
            filter_var($payload, FILTER_VALIDATE_EMAIL),
            'Precondition: PHP still accepts this as a valid email'
        );

        $html = $this->renderContact(['name' => 'Jane Doe', 'name_link' => $payload]);

        // The property that matters is that the payload cannot close the href
        // or open an element. esc_url() mangles it into an inert http:// URL,
        // so the literal text "onerror" survives inside the URL value -- which
        // is harmless, and not what this test is about.
        $this->assertStringNotContainsString('<img', $html);
        $this->assertSame(1, substr_count($html, '<a '), 'payload introduced an extra element');

        $this->assertSame(1, preg_match('/<a href="([^"]*)" rel="bookmark">/', $html, $m));
        $this->assertStringNotContainsString('<', $m[1]);
        $this->assertStringNotContainsString('>', $m[1]);
    }

    /**
     * Through the widget rather than the template.
     *
     * sanitize_email() reduces the payload to "img/src/onerror=alert1@a.com",
     * which is_email() accepts -- "/" and "=" are valid RFC atext characters --
     * so this does still become a mailto:. That is fine: it is a syntactically
     * valid address with every markup character removed, so it cannot break out
     * of the attribute. What must never happen is markup surviving into it.
     */
    public function test_quoted_local_part_payload_is_stripped_of_markup(): void
    {
        $payload = '"><img/src/onerror=alert(1)>"@a.com';

        Functions\when('get_post_type')->justReturn('agency');
        Functions\when('get_the_ID')->justReturn(8598);
        Functions\when('get_post_meta')->alias(fn($id, $key, $single = false) => match ($key) {
            'name'      => 'Jane Doe',
            'name_link' => $payload,
            default     => '',
        });

        $widget   = new AgencyContact();
        $instance = [];
        $widget->hasContent([], $instance);

        foreach (['<', '>', '"'] as $char) {
            $this->assertStringNotContainsString($char, $instance['name_link']);
        }

        // And it survives the template as a single well-formed anchor.
        $html = $this->renderContact($instance);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertSame(1, substr_count($html, '<a '));
    }


    /**
     * CustomContact passes this field through with no processing, so the
     * template itself has to escape rather than trusting its caller.
     */
    public function test_template_escapes_an_unprocessed_name_link(): void
    {
        $html = $this->renderContact([
            'name'      => 'Jane Doe',
            'name_link' => '" onmouseover="alert(1)',
        ]);

        $this->assertStringNotContainsString('onmouseover="alert(1)"', $html);
    }

    public function test_javascript_name_link_is_rejected(): void
    {
        $html = $this->renderContact(['name' => 'Jane Doe', 'name_link' => 'javascript:alert(1)']);

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_legitimate_name_link_still_renders(): void
    {
        $html = $this->renderContact([
            'name'      => 'Jane Doe',
            'name_link' => 'https://example.gov/contacts/jane-doe/',
        ]);

        $this->assertStringContainsString('href="https://example.gov/contacts/jane-doe/"', $html);
        $this->assertStringContainsString('Jane Doe', $html);
    }

    /**
     * hasContent() turns a bare email into a mailto:, and the result must
     * survive the template's escaping intact.
     */
    public function test_email_name_link_renders_as_mailto(): void
    {
        Functions\when('get_post_type')->justReturn('agency');
        Functions\when('get_the_ID')->justReturn(8598);
        Functions\when('get_post_meta')->alias(fn($id, $key, $single = false) => match ($key) {
            'name'      => 'Jane Doe',
            'name_link' => 'jane@example.gov',
            default     => '',
        });

        $widget   = new AgencyContact();
        $instance = [];
        $widget->hasContent([], $instance);

        $this->assertSame('mailto:jane@example.gov', $instance['name_link']);
        $this->assertStringContainsString(
            'href="mailto:jane@example.gov"',
            $this->renderContact($instance)
        );
    }

    // -----------------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------------

    /**
     * Renders the shared contact template the way printWidget() does.
     */
    private function renderContact(array $instance): string
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
}
