<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * #4011 removed every writer of this column; #4014 is the release N+1 half that
     * drops it now that a production release containing #4011 has shipped.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('legal_agreed_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->integer('legal_agreed_ms')->default(0);
        });
    }
};
