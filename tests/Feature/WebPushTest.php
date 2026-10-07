<?php

namespace Tests\Feature;

use App\Models\PushSubscriber;
use App\Models\User;
use App\Notifications\FutebolWebPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    private const INSTALLATION_ID = 'd7c59ecb-e89e-46e5-8295-d68f3185ea1d';

    private const ENDPOINT = 'https://push.example.test/subscriptions/123';

    public function test_a_device_can_subscribe_check_status_and_unsubscribe(): void
    {
        $payload = $this->subscriptionPayload();

        $this->postJson(route('push.store'), $payload)
            ->assertOk()
            ->assertJson(['subscribed' => true]);

        $this->assertDatabaseHas('push_subscribers', [
            'installation_key' => hash('sha256', self::INSTALLATION_ID),
            'daily_summary' => true,
            'kickoff_reminders' => true,
        ]);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => self::ENDPOINT]);

        $this->postJson(route('push.status'), ['installation_id' => self::INSTALLATION_ID])
            ->assertOk()
            ->assertJson([
                'subscribed' => true,
                'daily_summary' => true,
                'kickoff_reminders' => true,
            ]);

        $this->deleteJson(route('push.destroy'), [
            'installation_id' => self::INSTALLATION_ID,
            'endpoint' => self::ENDPOINT,
        ])->assertOk()->assertJson(['subscribed' => false]);

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => self::ENDPOINT]);
        $this->assertDatabaseCount('push_subscribers', 0);
    }

    public function test_push_admin_is_protected_and_can_send_an_internal_notification(): void
    {
        $this->get(route('admin.push.index'))->assertRedirect(route('admin.login'));

        Notification::fake();
        $subscriber = PushSubscriber::create([
            'installation_key' => hash('sha256', self::INSTALLATION_ID),
            'platform' => 'android',
            'device_type' => 'mobile',
            'daily_summary' => true,
            'kickoff_reminders' => false,
        ]);
        $subscriber->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token', 'aes128gcm');

        $this->actingAs(User::factory()->create())
            ->post(route('admin.push.store'), [
                'title' => 'Jogos de hoje',
                'body' => 'Confira as transmissões desta noite.',
                'target_url' => '/jogos/2026-10-07',
                'audience' => 'all',
            ])
            ->assertRedirect();

        Notification::assertSentTo($subscriber, FutebolWebPush::class);
        $this->assertDatabaseHas('push_notification_logs', [
            'type' => 'manual',
            'status' => 'sent',
            'recipients_count' => 1,
            'failed_count' => 0,
        ]);
    }

    public function test_push_admin_rejects_an_external_target_url(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.push.store'), [
                'title' => 'Título',
                'body' => 'Mensagem de teste',
                'target_url' => '//example.com/phishing',
                'audience' => 'all',
            ])
            ->assertSessionHasErrors('target_url');

        $this->assertDatabaseCount('push_notification_logs', 0);
    }

    /** @return array<string, mixed> */
    private function subscriptionPayload(): array
    {
        return [
            'installation_id' => self::INSTALLATION_ID,
            'subscription' => [
                'endpoint' => self::ENDPOINT,
                'keys' => [
                    'p256dh' => 'public-key',
                    'auth' => 'auth-token',
                ],
                'content_encoding' => 'aes128gcm',
            ],
            'daily_summary' => true,
            'kickoff_reminders' => true,
        ];
    }
}
