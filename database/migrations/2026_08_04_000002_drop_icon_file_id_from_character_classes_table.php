<?php

use App\Models\CharacterClass;
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
        // CharacterClass icons are now served from the assets repo via
        // CharacterClass::getIconUrlAttribute(), so the File-upload link is obsolete. Delete the
        // orphaned CharacterClass File rows with a query-builder mass delete on purpose: it bypasses
        // the File model's `deleting` hook, which would otherwise remove the physical asset images
        // those rows still point at.
        DB::table('files')->where('model_class', CharacterClass::class)->delete();

        Schema::table('character_classes', static function (Blueprint $table): void {
            $table->dropIndex('character_classes_icon_file_id_index');
            $table->dropColumn('icon_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_classes', static function (Blueprint $table): void {
            $table->string('icon_file_id');
            $table->index('icon_file_id', 'character_classes_icon_file_id_index');
        });
    }
};
