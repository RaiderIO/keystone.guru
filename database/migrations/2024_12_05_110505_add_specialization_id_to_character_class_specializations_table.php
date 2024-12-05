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
        Schema::table('character_class_specializations', function (Blueprint $table) {
            $table->integer('specialization_id')->after('id');
            $table->index('specialization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_class_specializations', function (Blueprint $table) {
            $table->dropColumn('specialization_id');
        });
    }
};
