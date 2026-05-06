<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mapping = [
            'Magic'   => 'magic',
            'Disease' => 'disease',
            'Poison'  => 'poison',
            'Curse'   => 'curse',
            'Enrage'  => 'enrage',
            ''        => 'none',
            'None'    => 'none',
            'N/A'     => 'n_a',
            'unknown' => 'unknown',
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
            'magic'   => 'Magic',
            'disease' => 'Disease',
            'poison'  => 'Poison',
            'curse'   => 'Curse',
            'enrage'  => 'Enrage',
            'none'    => '',
            'n_a'     => 'N/A',
            'unknown' => 'unknown',
        ];

        foreach ($mapping as $newValue => $oldValue) {
            DB::table('spells')
                ->where('dispel_type', sprintf('spelldispeltype.%s', $newValue))
                ->update(['dispel_type' => $oldValue]);
        }
    }
};
