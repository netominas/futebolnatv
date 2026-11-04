<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_settings', function (Blueprint $table): void {
            $table->boolean('auto_generate_news_images')->default(true)->after('active_provider');
        });

        Schema::table('news_articles', function (Blueprint $table): void {
            $table->string('hero_image_generation_status', 20)->nullable()->index()->after('hero_image_credit');
            $table->string('hero_image_generation_provider', 20)->nullable()->after('hero_image_generation_status');
            $table->string('hero_image_generation_model', 100)->nullable()->after('hero_image_generation_provider');
            $table->text('hero_image_generation_error')->nullable()->after('hero_image_generation_model');
            $table->timestamp('hero_image_generated_at')->nullable()->after('hero_image_generation_error');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table): void {
            $table->dropIndex(['hero_image_generation_status']);
            $table->dropColumn([
                'hero_image_generation_status',
                'hero_image_generation_provider',
                'hero_image_generation_model',
                'hero_image_generation_error',
                'hero_image_generated_at',
            ]);
        });

        Schema::table('ai_settings', function (Blueprint $table): void {
            $table->dropColumn('auto_generate_news_images');
        });
    }
};
