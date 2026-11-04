<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdSenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_load_adsense_but_admin_does_not(): void
    {
        $script = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6542760616729830';

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($script, false)
            ->assertSee('crossorigin="anonymous"', false);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertDontSee($script, false);
    }

    public function test_ads_txt_contains_the_authorized_google_seller(): void
    {
        $this->assertSame(
            "google.com, pub-6542760616729830, DIRECT, f08c47fec0942fa0\n",
            file_get_contents(public_path('ads.txt')),
        );
    }
}
