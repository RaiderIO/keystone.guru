<?php

use App\Models\Faction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Faction icons are now served from the assets repo via Faction::getIconUrlAttribute(), so
        // the File-upload link is obsolete. Delete the orphaned Faction File rows with a
        // query-builder mass delete on purpose: it bypasses the File model's `deleting` hook, which
        // would otherwise remove the physical asset images those rows still point at.
        DB::table('files')->where('model_class', Faction::class)->delete();

        Schema::table('factions', static function (Blueprint $table): void {
            $table->dropIndex('factions_icon_file_id_index');
            $table->dropColumn('icon_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factions', static function (Blueprint $table): void {
            $table->integer('icon_file_id')->after('id');
            $table->index('icon_file_id', 'factions_icon_file_id_index');
        });
    }
};
