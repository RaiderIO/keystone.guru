<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('map_facade_style', [
                User::MAP_FACADE_STYLE_SPLIT_FLOORS,
                User::MAP_FACADE_STYLE_FACADE,
            ])
                ->default(User::MAP_FACADE_STYLE_FACADE)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('map_facade_style', [
                User::MAP_FACADE_STYLE_SPLIT_FLOORS,
                User::MAP_FACADE_STYLE_FACADE,
            ])
                ->default(User::MAP_FACADE_STYLE_SPLIT_FLOORS)
                ->change();
        });
    }
};
