<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->string('color_animated')->nullable()->after('faction');
            $table->string('color')->nullable()->after('faction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->dropColumn('color');
            $table->dropColumn('color_animated');
        });
    }
};
