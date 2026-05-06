<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jobs_failed', function (Blueprint $table) {
            $table->string('uuid')->after('id')->nullable()->unique();
        });

        DB::table('jobs_failed')->whereNull('uuid')->cursor()->each(function ($job) {
            DB::table('jobs_failed')
                ->where('id', $job->id)
                ->update(['uuid' => (string)Illuminate\Support\Str::uuid()]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs_failed', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
