<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the standalone AgencySocial widget.
 *
 * This widget was disabled by a hardcoded `return false;` in hasContent() from
 * 2016 (commit 89802cb, issue #691) until issue #2933. It stayed registered the
 * whole time, so editors could add "Agency social media" in Page Builder and
 * get a silently blank widget. These tests pin the behaviour now that it is
 * live again.
 */
class AgencySocialWidgetTest extends TestCase
{
    private AgencySocial $widget;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->widget = new AgencySocial();

        // hasContent() resolves the agency ID from the current post.
        Functions\when('get_post_type')->justReturn('agency');
        Functions\when('get_the_ID')->justReturn(8598);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * @param array $map service key => stored meta value
     */
    private function stubSocialMeta(array $map): void
    {
        Functions\when('get_post_meta')->alias(function ($id, $key, $single = false) use ($map) {
            $service = str_starts_with($key, 'social_') ? substr($key, 7) : null;
            return $service !== null && isset($map[$service]) ? $map[$service] : '';
        });
    }

    private function renderWith(array $map, array $instance = []): string
    {
        $this->stubSocialMeta($map);
        $this->widget->hasContent([], $instance);

        ob_start();
        $this->widget->printWidget([], $instance);
        return ob_get_clean();
    }

    // -----------------------------------------------------------------------
    // hasContent()
    // -----------------------------------------------------------------------

    /**
     * The 2016 regression: hasContent() returned false unconditionally, so
     * ProudWidget::widget() bailed before printing anything at all.
     */
    public function test_has_content_is_true_when_accounts_exist(): void
    {
        $this->stubSocialMeta(['facebook' => 'https://facebook.com/sr']);
        $instance = [];

        $this->assertTrue($this->widget->hasContent([], $instance));
    }

    public function test_has_content_is_false_when_no_accounts(): void
    {
        $this->stubSocialMeta([]);
        $instance = [];

        $this->assertFalse($this->widget->hasContent([], $instance));
    }

    /**
     * Every department has all ten social_* meta keys; most are blank. Blank
     * keys must not count as content.
     */
    public function test_has_content_is_false_when_all_accounts_blank(): void
    {
        $this->stubSocialMeta(array_fill_keys(
            array_keys(Proud\Agency\agency_social_services()),
            ''
        ));
        $instance = [];

        $this->assertFalse($this->widget->hasContent([], $instance));
    }

    /**
     * hasContent() takes $instance by reference and is the only thing that
     * populates it before printWidget() runs.
     */
    public function test_has_content_populates_instance_by_reference(): void
    {
        $this->stubSocialMeta(['facebook' => 'https://facebook.com/sr']);
        $instance = [];

        $this->widget->hasContent([], $instance);

        $this->assertSame(['facebook' => 'https://facebook.com/sr'], $instance['social']);
        $this->assertSame('https://facebook.com/sr', $instance['social_facebook']);
    }

    /**
     * On a child page the agency ID comes from $pageInfo['parent_post'].
     */
    public function test_resolves_parent_post_when_not_on_an_agency(): void
    {
        global $pageInfo;
        $pageInfo = ['parent_post' => 4242];
        Functions\when('get_post_type')->justReturn('page');

        $seen = null;
        Functions\when('get_post_meta')->alias(function ($id, $key, $single = false) use (&$seen) {
            $seen = $id;
            return '';
        });

        $instance = [];
        $this->widget->hasContent([], $instance);

        $this->assertSame(4242, $seen);
        $pageInfo = null;
    }

    // -----------------------------------------------------------------------
    // printWidget()
    // -----------------------------------------------------------------------

    public function test_renders_icons_for_each_account(): void
    {
        $html = $this->renderWith([
            'facebook'  => 'https://facebook.com/sr',
            'instagram' => 'https://instagram.com/sr',
        ]);

        $this->assertStringContainsString('list-inline', $html);
        $this->assertStringContainsString('https://facebook.com/sr', $html);
        $this->assertStringContainsString('https://instagram.com/sr', $html);
        $this->assertSame(2, substr_count($html, '<li>'));
    }

    public function test_renders_nothing_without_accounts(): void
    {
        $this->assertSame('', trim($this->renderWith([])));
    }

    /**
     * The widget and the contact template must agree on icons; they drifted
     * apart before and that is what #2933 was.
     */
    public function test_icon_classes_match_the_shared_helper(): void
    {
        foreach (array_keys(Proud\Agency\agency_social_services()) as $service) {
            $html = $this->renderWith([$service => 'https://example.com/acct']);

            $this->assertStringContainsString(
                Proud\Agency\social_icon_classes($service),
                $html,
                "Widget icon markup for '{$service}' does not match social_icon_classes()"
            );
        }
    }

    /**
     * The icons are decorative, so each link needs a text label for screen
     * readers. The pre-#2933 markup had none.
     */
    public function test_links_have_screen_reader_labels(): void
    {
        $html = $this->renderWith(['facebook' => 'https://facebook.com/sr']);

        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('<span class="sr-only">Facebook</span>', $html);
        $this->assertStringContainsString('title="Facebook"', $html);
    }

    public function test_external_links_carry_noopener(): void
    {
        $html = $this->renderWith(['facebook' => 'https://facebook.com/sr']);

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    // -----------------------------------------------------------------------
    // Escaping — this markup printed $url unescaped before #2933
    // -----------------------------------------------------------------------

    public function test_url_is_escaped_in_href(): void
    {
        $html = $this->renderWith([
            'facebook' => 'https://facebook.com/"><img src=x onerror=alert(1)>',
        ]);

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('onerror=alert(1)>', $html);
    }

    public function test_javascript_scheme_is_rejected(): void
    {
        $html = $this->renderWith(['facebook' => 'javascript:alert(1)']);

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_obfuscated_javascript_scheme_is_rejected(): void
    {
        $html = $this->renderWith(['facebook' => "java\nscript:alert(1)"]);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    public function test_webcal_allowed_for_ical_but_not_others(): void
    {
        $this->assertStringContainsString(
            'webcal://example.com/feed.ics',
            $this->renderWith(['ical' => 'webcal://example.com/feed.ics'])
        );
        $this->assertStringNotContainsString(
            'webcal://',
            $this->renderWith(['facebook' => 'webcal://example.com/feed.ics'])
        );
    }

    /**
     * A stray unsupported key in $instance['social'] must be skipped rather
     * than emitted with a guessed class.
     */
    public function test_unsupported_service_is_skipped_at_render(): void
    {
        $instance = ['social' => ['myspace' => 'https://myspace.com/sr']];

        ob_start();
        $this->widget->printWidget([], $instance);
        $html = ob_get_clean();

        $this->assertStringNotContainsString('myspace', $html);
        $this->assertStringNotContainsString('<li>', $html);
    }
}
