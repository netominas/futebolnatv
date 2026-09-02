<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_publisher_pages_are_public_and_linked_in_footer(): void
    {
        foreach (['pages.about', 'pages.contact', 'pages.privacy', 'pages.cookies', 'pages.terms', 'pages.editorial'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Futebol na TV');
        }

        $this->get(route('home'))->assertSee(route('pages.privacy'))->assertSee(route('pages.contact'))->assertSee(route('pages.terms'));
    }
}
