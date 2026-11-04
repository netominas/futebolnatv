<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('active_provider', 20)->default('openai');
            $table->text('openai_api_key')->nullable();
            $table->string('openai_model', 100)->default('gpt-image-2.5-flare');
            $table->text('google_api_key')->nullable();
            $table->string('google_model', 100)->default('gemini-3.1-flash-image');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
