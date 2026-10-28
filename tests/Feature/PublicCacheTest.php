<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_ready_for_public_edge_caching_without_session_cookies(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Futebol-Cache', 'public');
        $response->assertHeader(
            'Cloudflare-CDN-Cache-Control',
            'public, max-age=30, must-revalidate, stale-if-error=300',
        );
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertFalse($response->headers->has('Set-Cookie'));
        $response->assertDontSee('name="csrf-token"', false);
    }

    public function test_static_content_pages_keep_the_longer_edge_cache_without_browser_html_cache(): void
    {
        $response = $this->get(route('pages.about'));

        $response->assertOk()
            ->assertHeader('Cloudflare-CDN-Cache-Control', 'public, max-age=60, stale-while-revalidate=300, stale-if-error=86400');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_admin_login_is_not_marked_for_public_caching(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertOk();
        $response->assertHeaderMissing('X-Futebol-Cache');
        $response->assertSee('name="_token"', false);
    }

    public function test_search_is_not_marked_for_public_caching(): void
    {
        $this->get(route('search', ['q' => 'Flamengo']))
            ->assertOk()
            ->assertHeaderMissing('X-Futebol-Cache');
    }
}
