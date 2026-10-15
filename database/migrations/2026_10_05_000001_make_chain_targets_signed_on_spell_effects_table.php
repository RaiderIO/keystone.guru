<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The client's SpellEffect data carries negative chain target counts (-1, -10). Non-strict MySQL stored them as 0
     * in the unsigned column; strict mode rejects the write.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `spell_effects` MODIFY `chain_targets` SMALLINT NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('UPDATE `spell_effects` SET `chain_targets` = 0 WHERE `chain_targets` < 0');
        DB::statement('ALTER TABLE `spell_effects` MODIFY `chain_targets` SMALLINT UNSIGNED NOT NULL');
    }
};
