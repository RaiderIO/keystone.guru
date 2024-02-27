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
        Schema::table('floor_union_areas', function (Blueprint $table) {
            $table->text('vertices_json')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('floor_union_areas', function (Blueprint $table) {
            $table->string('vertices_json')->change();
        });
    }
};
