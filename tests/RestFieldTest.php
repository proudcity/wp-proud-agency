<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the agency REST field registered in Agency::agency_rest_support().
 *
 * Issue #2981: the field was registered with the bare string
 * 'agency_rest_metadata', but that is a method on Agency, not a global
 * function. PHP 7 only warned; PHP 8 throws a TypeError in
 * WP_REST_Controller::add_additional_fields_to_object(), so every
 * /wp/v2/agencies request returned 500.
 */
class RestFieldTest extends TestCase
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

    public function test_meta_field_get_callback_is_callable(): void
    {
        $registered = null;
        Functions\expect('register_rest_field')
            ->once()
            ->andReturnUsing(function ($object_type, $attribute, $args) use (&$registered) {
                $registered = compact('object_type', 'attribute', 'args');
            });

        (new Proud\Agency\Agency())->agency_rest_support();

        $this->assertSame('agency', $registered['object_type']);
        $this->assertSame('meta', $registered['attribute']);
        $this->assertTrue(
            is_callable($registered['args']['get_callback']),
            'get_callback must resolve to a callable, or PHP 8 fatals on every agencies REST request'
        );
    }
}
