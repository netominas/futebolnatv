<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandingAssetsTest extends TestCase
{
    public function test_brand_assets_are_available_and_rendered_in_header_and_footer(): void
    {
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('images/favicon.png'));
        $this->assertFileExists(public_path('images/futebol-na-tv-logo.png'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(asset('images/futebol-na-tv-logo.png'), false)
            ->assertSee('alt="Futebol na TV"', false);
    }
}
