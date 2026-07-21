<?php

use App\Models\Expansion;
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
        // Expansion icons are now served from the assets repo via Expansion::getIconUrl(), so the
        // File-upload link is obsolete. Delete the orphaned expansion File rows with a query-builder
        // mass delete on purpose: it bypasses the File model's `deleting` hook, which would otherwise
        // remove the physical asset images those rows still point at.
        DB::table('files')->where('model_class', Expansion::class)->delete();

        Schema::table('expansions', static function (Blueprint $table): void {
            $table->dropIndex('expansions_icon_file_id_index');
            $table->dropColumn('icon_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expansions', static function (Blueprint $table): void {
            $table->integer('icon_file_id')->after('id');
            $table->index('icon_file_id', 'expansions_icon_file_id_index');
        });
    }
};
