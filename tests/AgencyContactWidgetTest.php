<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AgencyContact::hasContent(), which loads a department's contact
 * details and social accounts out of post meta onto the widget instance before
 * the shared template renders them.
 *
 * The social half of this is the #2933 fix: it now delegates to
 * agency_social_meta() so the flat social_<service> keys the template scans for
 * actually exist on $instance.
 */
class AgencyContactWidgetTest extends TestCase
{
    private AgencyContact $widget;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->widget = new AgencyContact();

        Functions\when('get_post_type')->justReturn('agency');
        Functions\when('get_the_ID')->justReturn(8598);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * @param array $map meta key => stored value
     */
    private function stubMeta(array $map): void
    {
        Functions\when('get_post_meta')->alias(fn($id, $key, $single = false) => $map[$key] ?? '');
    }

    // -----------------------------------------------------------------------
    // The #2933 fix
    // -----------------------------------------------------------------------

    /**
     * The template scans $instance for flat social_<service> keys. Before the
     * fix hasContent() wrote only $instance['social'], so nothing rendered.
     */
    public function test_populates_flat_social_keys_for_the_template(): void
    {
        $this->stubMeta([
            'social_facebook'  => 'https://www.facebook.com/sanrafaellibraryrec/',
            'social_instagram' => 'https://www.instagram.com/sanrafaellibraryrec/',
        ]);
        $instance = [];

        $this->widget->hasContent([], $instance);

        $this->assertSame('https://www.facebook.com/sanrafaellibraryrec/', $instance['social_facebook']);
        $this->assertSame('https://www.instagram.com/sanrafaellibraryrec/', $instance['social_instagram']);
    }

    /**
     * The nested array still drives the emptiness check below, so both shapes
     * have to be written.
     */
    public function test_populates_nested_social_array_as_well(): void
    {
        $this->stubMeta(['social_facebook' => 'https://facebook.com/sr']);
        $instance = [];

        $this->widget->hasContent([], $instance);

        $this->assertSame(['facebook' => 'https://facebook.com/sr'], $instance['social']);
    }

    /**
     * A department with nothing but social accounts must still render. This is
     * the San Rafael Library and Recreation case from the issue.
     */
    public function test_social_accounts_alone_are_enough_to_render(): void
    {
        $this->stubMeta(['social_facebook' => 'https://facebook.com/sr']);
        $instance = [];

        $this->assertTrue($this->widget->hasContent([], $instance));
    }

    // -----------------------------------------------------------------------
    // Contact fields
    // -----------------------------------------------------------------------

    public function test_loads_contact_fields_from_meta(): void
    {
        $this->stubMeta([
            'name'       => 'Jane Doe',
            'name_title' => 'Director',
            'email'      => 'library@example.gov',
            'phone'      => '555-1234',
            'fax'        => '555-5678',
            'sms'        => '555-9012',
            'address'    => '1100 E St',
            'hours'      => 'Mon-Fri 9-5',
        ]);
        $instance = [];

        $this->widget->hasContent([], $instance);

        $this->assertSame('Jane Doe', $instance['name']);
        $this->assertSame('Director', $instance['name_title']);
        $this->assertSame('library@example.gov', $instance['email']);
        $this->assertSame('555-1234', $instance['phone']);
        $this->assertSame('Mon-Fri 9-5', $instance['hours']);
    }

    /**
     * A bare email in the contact link field becomes a mailto:.
     */
    public function test_email_name_link_becomes_mailto(): void
    {
        $this->stubMeta(['name_link' => 'jane@example.gov']);
        $instance = [];

        $this->widget->hasContent([], $instance);

        $this->assertSame('mailto:jane@example.gov', $instance['name_link']);
    }

    public function test_url_name_link_is_left_as_a_url(): void
    {
        $this->stubMeta(['name_link' => 'https://example.gov/staff/jane']);
        $instance = [];

        $this->widget->hasContent([], $instance);

        $this->assertSame('https://example.gov/staff/jane', $instance['name_link']);
    }

    // -----------------------------------------------------------------------
    // Emptiness
    // -----------------------------------------------------------------------

    public function test_no_content_when_all_meta_is_empty(): void
    {
        $this->stubMeta([]);
        $instance = [];

        $this->assertFalse($this->widget->hasContent([], $instance));
    }

    /**
     * fax and sms are deliberately not in the emptiness check: a department
     * with only a fax number would render a widget that is almost blank.
     */
    public function test_fax_or_sms_alone_does_not_render(): void
    {
        $this->stubMeta(['fax' => '555-5678', 'sms' => '555-9012']);
        $instance = [];

        $this->assertFalse($this->widget->hasContent([], $instance));
    }

    #[DataProvider('contentFieldProvider')]
    public function test_any_single_content_field_renders(string $key, string $value): void
    {
        $this->stubMeta([$key => $value]);
        $instance = [];

        $this->assertTrue($this->widget->hasContent([], $instance));
    }

    public static function contentFieldProvider(): array
    {
        return [
            'name'    => ['name', 'Jane Doe'],
            'email'   => ['email', 'library@example.gov'],
            'phone'   => ['phone', '555-1234'],
            'address' => ['address', '1100 E St'],
            'hours'   => ['hours', 'Mon-Fri 9-5'],
        ];
    }

    /**
     * Widget settings already on the instance (the Page Builder title) must
     * survive the meta merge.
     */
    public function test_existing_instance_keys_are_preserved(): void
    {
        $this->stubMeta(['social_facebook' => 'https://facebook.com/sr']);
        $instance = ['title' => 'Connect with us'];

        $this->widget->hasContent([], $instance);

        $this->assertSame('Connect with us', $instance['title']);
    }
}
