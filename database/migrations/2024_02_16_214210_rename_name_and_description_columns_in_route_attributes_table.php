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
        Schema::table('route_attributes', function (Blueprint $table) {
            $table->renameColumn('name', 'key');
            $table->renameColumn('description', 'name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('route_attributes', function (Blueprint $table) {
            $table->renameColumn('name', 'description');
            $table->renameColumn('key', 'name');
        });
    }
};
