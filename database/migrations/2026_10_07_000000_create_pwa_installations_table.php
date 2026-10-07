<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pwa_installations', function (Blueprint $table) {
            $table->id();
            $table->char('installation_key', 64)->unique();
            $table->string('status', 20)->default('interest')->index();
            $table->string('platform', 30)->default('other')->index();
            $table->string('device_type', 20)->default('unknown');
            $table->timestamp('installed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_installations');
    }
};
