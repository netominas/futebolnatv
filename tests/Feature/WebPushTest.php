<?php

namespace Tests\Feature;

use App\Jobs\SendPushNotification;
use App\Models\PushNotificationLog;
use App\Models\PushSubscriber;
use App\Models\User;
use App\Notifications\FutebolWebPush;
use App\Services\WebPushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    private const INSTALLATION_ID = 'd7c59ecb-e89e-46e5-8295-d68f3185ea1d';

    private const ENDPOINT = 'https://push.example.test/subscriptions/123';

    public function test_a_device_can_subscribe_check_status_and_unsubscribe(): void
    {
        $payload = $this->subscriptionPayload();

        $this->withHeader('Origin', config('app.url'))->postJson(route('push.store'), $payload)
            ->assertOk()
            ->assertJson(['subscribed' => true]);

        $this->assertDatabaseHas('push_subscribers', [
            'installation_key' => hash('sha256', self::INSTALLATION_ID),
            'daily_summary' => true,
            'kickoff_reminders' => true,
        ]);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => self::ENDPOINT]);

        $this->withHeader('Origin', config('app.url'))
            ->postJson(route('push.status'), ['installation_id' => self::INSTALLATION_ID])
            ->assertOk()
            ->assertJson([
                'subscribed' => true,
                'daily_summary' => true,
                'kickoff_reminders' => true,
            ]);

        $this->withHeader('Origin', config('app.url'))
            ->deleteJson(route('push.destroy'), [
                'installation_id' => self::INSTALLATION_ID,
                'endpoint' => self::ENDPOINT,
            ])->assertOk()->assertJson(['subscribed' => false]);

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => self::ENDPOINT]);
        $this->assertDatabaseCount('push_subscribers', 0);
    }

    public function test_a_cross_origin_request_cannot_read_push_status(): void
    {
        $this->withHeader('Origin', 'https://example.com')
            ->postJson(route('push.status'), ['installation_id' => self::INSTALLATION_ID])
            ->assertForbidden();

        $this->assertDatabaseCount('push_subscribers', 0);
    }

    public function test_push_admin_is_protected_and_can_queue_an_internal_notification(): void
    {
        $this->get(route('admin.push.index'))->assertRedirect(route('admin.login'));

        Queue::fake();
        Storage::fake('public');
        PushSubscriber::create([
            'installation_key' => hash('sha256', self::INSTALLATION_ID),
            'platform' => 'android',
            'device_type' => 'mobile',
            'daily_summary' => true,
            'kickoff_reminders' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.push.store'), [
                'title' => 'Jogos de hoje',
                'body' => 'Confira as transmissões desta noite.',
                'target_url' => '/jogos/2026-10-07',
                'audience' => 'all',
                'icon' => $this->fakePng('icone.png', 200, 100),
                'image' => $this->fakePng('destaque.png', 400, 200),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Notificação adicionada à fila de envio.');

        $this->assertDatabaseHas('push_notification_logs', [
            'type' => 'manual',
            'status' => 'pending',
            'recipients_count' => 0,
            'failed_count' => 0,
        ]);

        $log = PushNotificationLog::firstOrFail();
        Queue::assertPushedOn(
            'push',
            SendPushNotification::class,
            fn (SendPushNotification $job) => $job->notificationLogId === $log->id
                && $job->connection === 'redis_push',
        );
        Storage::disk('public')->assertExists($log->icon_path);
        Storage::disk('public')->assertExists($log->image_path);
    }

    public function test_queued_push_job_sends_and_finishes_the_campaign(): void
    {
        Notification::fake();
        $subscriber = PushSubscriber::create([
            'installation_key' => hash('sha256', self::INSTALLATION_ID),
            'platform' => 'android',
            'device_type' => 'mobile',
            'daily_summary' => true,
            'kickoff_reminders' => false,
        ]);
        $subscriber->updatePushSubscription(self::ENDPOINT, 'public-key', 'auth-token', 'aes128gcm');
        $log = PushNotificationLog::create([
            'type' => 'manual',
            'title' => 'Jogos de hoje',
            'body' => 'Confira as transmissões desta noite.',
            'target_url' => '/jogos/2026-10-07',
            'audience' => 'all',
        ]);

        (new SendPushNotification($log->id))->handle(app(WebPushSender::class));

        Notification::assertSentTo($subscriber, FutebolWebPush::class);
        $this->assertDatabaseHas('push_notification_logs', [
            'id' => $log->id,
            'status' => 'sent',
            'recipients_count' => 1,
            'failed_count' => 0,
        ]);
        $this->assertNotNull($log->fresh()->sent_at);
        $this->assertNotNull($subscriber->fresh()->last_notified_at);
    }

    public function test_a_permanently_failed_push_job_marks_the_campaign_as_failed(): void
    {
        $log = PushNotificationLog::create([
            'type' => 'manual',
            'title' => 'Jogos de hoje',
            'body' => 'Confira as transmissões desta noite.',
            'target_url' => '/',
            'audience' => 'all',
        ]);

        (new SendPushNotification($log->id))->failed(new \RuntimeException('Falha simulada'));

        $this->assertDatabaseHas('push_notification_logs', [
            'id' => $log->id,
            'status' => 'failed',
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

    private function fakePng(string $name, int $width, int $height): UploadedFile
    {
        $row = "\0".str_repeat("\0", $width * 3);
        $pixels = str_repeat($row, $height);
        $compressed = gzcompress($pixels, 9);

        $png = "\x89PNG\r\n\x1a\n"
            .$this->pngChunk('IHDR', pack('NNC5', $width, $height, 8, 2, 0, 0, 0))
            .$this->pngChunk('IDAT', $compressed === false ? '' : $compressed)
            .$this->pngChunk('IEND', '');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
