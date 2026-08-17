<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The 2026_04_20_224500_update_spell_dispel_type_to_translation_keys migration converted every
     * `dispel_type` value known at the time, but writers producing unprefixed values (#4095) kept the
     * mixed state alive - some rows still hold a bare `magic`/`disease`/`n_a`/`unknown`, and rows
     * created since (with no dispel type known yet) hold an empty string rather than the prefixed
     * "unknown" key.
     */
    public function up(): void
    {
        $mapping = [
            'magic'   => 'magic',
            'disease' => 'disease',
            'poison'  => 'poison',
            'curse'   => 'curse',
            'enrage'  => 'enrage',
            'none'    => 'none',
            'n_a'     => 'n_a',
            'unknown' => 'unknown',
            ''        => 'unknown',
        ];

        foreach ($mapping as $oldValue => $newValue) {
            DB::table('spells')
                ->where('dispel_type', $oldValue)
                ->update(['dispel_type' => sprintf('spelldispeltype.%s', $newValue)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $mapping = [
            'magic'   => 'magic',
            'disease' => 'disease',
            'poison'  => 'poison',
            'curse'   => 'curse',
            'enrage'  => 'enrage',
            'none'    => 'none',
            'n_a'     => 'n_a',
            'unknown' => 'unknown',
        ];

        foreach ($mapping as $newValue => $oldValue) {
            DB::table('spells')
                ->where('dispel_type', sprintf('spelldispeltype.%s', $newValue))
                ->update(['dispel_type' => $oldValue]);
        }
    }
};
