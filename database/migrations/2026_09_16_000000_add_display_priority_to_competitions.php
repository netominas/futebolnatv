<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedSmallInteger('display_priority')->default(1000)->after('local_logo_path');
        });

        foreach (config('competition_priorities.initial') as $name => $priority) {
            DB::table('competitions')->where('name', $name)->update(['display_priority' => $priority]);
        }
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('display_priority');
        });
    }
};
