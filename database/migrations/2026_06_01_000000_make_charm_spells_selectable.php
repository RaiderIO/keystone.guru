<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /** @var int[] Control Undead (111673) and Subjugate Demon (1098) */
    private const array CHARM_SPELL_IDS = [111673, 1098];

    public function up(): void
    {
        DB::table('spells')
            ->whereIn('id', self::CHARM_SPELL_IDS)
            ->update(['selectable' => true]);
    }

    public function down(): void
    {
        DB::table('spells')
            ->whereIn('id', self::CHARM_SPELL_IDS)
            ->update(['selectable' => false]);
    }
};
