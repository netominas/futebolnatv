<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressiveWebAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_exposes_pwa_metadata(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('/manifest.json', false);
        $response->assertSee('apple-mobile-web-app-capable', false);
        $response->assertSee('data-pwa-floating', false);
        $response->assertSee('data-push-floating', false);
        $response->assertSee('name="webpush-public-key"', false);
    }

    public function test_pwa_public_files_are_valid(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('images/pwa/icon-192.png'));
        $this->assertFileExists(public_path('images/pwa/icon-512.png'));
        $this->assertFileExists(public_path('images/pwa/icon-maskable-512.png'));
        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('futebol-na-tv-v4', $serviceWorker);
        $this->assertStringContainsString("'/admin'", $serviceWorker);
        $this->assertStringContainsString("fetch(event.request, { cache: 'no-store' })", $serviceWorker);
        $this->assertSame(1, substr_count($serviceWorker, 'cache.put(event.request, copy)'));
        $this->assertStringContainsString("register('/sw.js?v=4'", file_get_contents(resource_path('js/app.js')));
    }

    public function test_pwa_script_emits_the_confirmed_installation_event_for_tag_manager(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("window.addEventListener('appinstalled'", $script);
        $this->assertStringContainsString("event: 'pwa_install'", $script);
        $this->assertStringContainsString('pwa_install_id: installId', $script);
        $this->assertStringContainsString('pwa_platform: pwaPlatform()', $script);
        $this->assertStringContainsString("trackPwaInstallation('appinstalled')", $script);
        $this->assertStringContainsString('pwa-install-conversion-sent', $script);
    }
}
