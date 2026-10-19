<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->text('bio')->nullable()->after('email');
        });

        DB::table('users')->orderBy('id')->eachById(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'slug' => Str::slug($user->name).'-'.$user->id,
            ]);
        });

        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->default('daily_schedule')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->date('coverage_date')->nullable()->index();
            $table->string('title', 160);
            $table->string('slug')->unique();
            $table->string('subtitle', 240)->nullable();
            $table->text('excerpt');
            $table->longText('body');
            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('hero_image_path')->nullable();
            $table->string('hero_image_alt')->nullable();
            $table->string('hero_image_caption')->nullable();
            $table->string('hero_image_credit')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('last_fact_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fixture_news_article', function (Blueprint $table) {
            $table->foreignId('news_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
            $table->primary(['news_article_id', 'fixture_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixture_news_article');
        Schema::dropIfExists('news_articles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'bio']);
        });
    }
};
