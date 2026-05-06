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
        Schema::table('releases', function (Blueprint $table) {
            $table->boolean('released')->default(false)->after('spotlight');
        });

        // For now, assume everything was released
        /** @noinspection SqlWithoutWhere */
        DB::update('UPDATE `releases` SET `released` = 1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** @noinspection SqlWithoutWhere */
        DB::update('UPDATE `releases` SET `released` = 0');

        Schema::table('releases', function (Blueprint $table) {
            $table->dropColumn('released');
        });
    }
};
