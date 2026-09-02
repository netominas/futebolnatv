<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wosti_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wosti_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('broadcast_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wosti_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wosti_id')->unique();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->dateTime('starts_at')->index();
            $table->boolean('is_listed')->default(true)->index();
            $table->timestamp('last_seen_at');
            $table->timestamps();
        });

        Schema::create('broadcast_channel_fixture', function (Blueprint $table) {
            $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('broadcast_channel_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['fixture_id', 'broadcast_channel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_channel_fixture');
        Schema::dropIfExists('fixtures');
        Schema::dropIfExists('broadcast_channels');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('competitions');
    }
};
