<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('local_logo_path')->nullable()->after('image');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->string('local_logo_path')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('local_logo_path');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('local_logo_path');
        });
    }
};
