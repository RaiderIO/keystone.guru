<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * aura is combat-log-derived behavior and is no longer present in spells.json (#3354). The seeder
     * therefore inserts spell rows without it, which fails on a strict connection because the NOT NULL
     * column has no default. Give it a default of false so inserts succeed; preserveColumns() re-applies
     * the live value for existing rows afterwards.
     */
    public function up(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->boolean('aura')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->boolean('aura')->change();
        });
    }
};
