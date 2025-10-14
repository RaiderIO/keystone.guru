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
        /** @noinspection SqlWithoutWhere */
        DB::update('
            UPDATE `users` SET `locale` = REPLACE(`locale`, "-", "_")
        ');

        Schema::table('users', function (Blueprint $table) {
            $table->string('locale')->default('en_US')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale')->default('en-US')->change();
        });
    }
};
