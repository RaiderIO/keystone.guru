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
        Schema::table('paid_tiers', function (Blueprint $table) {
            $table->string('key')->after('id');

            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paid_tiers', function (Blueprint $table) {
            $table->dropIndex(['key']);
            $table->dropColumn('key');
        });
    }
};
