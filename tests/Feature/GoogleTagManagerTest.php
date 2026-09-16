<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleTagManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_google_tag_manager_in_head_and_body(): void
    {
        foreach ([route('home'), route('teams.index'), route('competitions.index'), route('channels.index'), route('pages.about')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(2, substr_count($html, 'GTM-KNQ76J3L'), "GTM não foi renderizado corretamente em {$url}");
            $this->assertStringContainsString('https://www.googletagmanager.com/gtm.js?id=', $html);
            $this->assertStringContainsString('https://www.googletagmanager.com/ns.html?id=GTM-KNQ76J3L', $html);
            $this->assertLessThan(strpos($html, '</head>'), strpos($html, 'googletagmanager.com/gtm.js'));
            $this->assertGreaterThan(strpos($html, '<body'), strpos($html, 'googletagmanager.com/ns.html'));
        }
    }
}
