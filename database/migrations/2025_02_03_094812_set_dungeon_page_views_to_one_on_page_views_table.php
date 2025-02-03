<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        set_time_limit(-1);
        DB::update('UPDATE `page_views` SET `source` = 1 WHERE `model_class` = "App\\\\Models\\\\Dungeon" AND `source` = 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('one_on_page_views', function (Blueprint $table) {
            //
        });
    }
};
