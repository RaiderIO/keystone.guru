<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->text('comment')->nullable()->default(null)->change();
        });

        DB::update('UPDATE `map_icons` SET `comment` = null WHERE `comment` = ""');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('map_icons', function (Blueprint $table) {
            $table->text('comment')->nullable(false)->default('')->change();
        });
    }
};
