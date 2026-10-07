<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NotificationChannels\WebPush\PushSubscription;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscribers', function (Blueprint $table) {
            $table->id();
            $table->char('installation_key', 64)->unique();
            $table->string('platform', 30)->default('other')->index();
            $table->string('device_type', 20)->default('unknown');
            $table->boolean('daily_summary')->default(true)->index();
            $table->boolean('kickoff_reminders')->default(false)->index();
            $table->timestamp('permission_granted_at')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
            $table->string('endpoint', PushSubscription::ENDPOINT_MAX_LENGTH)->charset('ascii')->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });

        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->string('title');
            $table->text('body');
            $table->string('target_url', 1024);
            $table->string('audience', 30)->default('all');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('dedupe_key')->nullable()->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('push_subscribers');
    }
};
