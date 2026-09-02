<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminChannelLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_authenticated_user_can_manage_channel_links(): void
    {
        $channel = BroadcastChannel::create(['wosti_id' => 1, 'name' => 'Canal Azul', 'slug' => 'canal-azul']);

        $this->get(route('admin.channels.index'))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create())->put(route('admin.channels.update', $channel), ['external_url' => 'https://example.com/assinar'])->assertRedirect();

        $this->assertDatabaseHas('broadcast_channels', ['id' => $channel->id, 'external_url' => 'https://example.com/assinar']);
    }
}
