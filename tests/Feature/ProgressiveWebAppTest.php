<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProgressiveWebAppTest extends TestCase
{
    public function test_home_exposes_pwa_metadata(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('/manifest.json', false);
        $response->assertSee('apple-mobile-web-app-capable', false);
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
    }
}
