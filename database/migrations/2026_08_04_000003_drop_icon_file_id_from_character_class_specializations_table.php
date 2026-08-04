<?php

use App\Models\CharacterClassSpecialization;
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
        // CharacterClassSpecialization icons are now served from the assets repo via
        // CharacterClassSpecialization::getIconUrlAttribute(), so the File-upload link is obsolete.
        // Delete the orphaned CharacterClassSpecialization File rows with a query-builder mass
        // delete on purpose: it bypasses the File model's `deleting` hook, which would otherwise
        // remove the physical asset images those rows still point at.
        DB::table('files')->where('model_class', CharacterClassSpecialization::class)->delete();

        Schema::table('character_class_specializations', static function (Blueprint $table): void {
            $table->dropIndex('character_class_specializations_icon_file_id_index');
            $table->dropColumn('icon_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_class_specializations', static function (Blueprint $table): void {
            $table->string('icon_file_id');
            $table->index('icon_file_id', 'character_class_specializations_icon_file_id_index');
        });
    }
};
