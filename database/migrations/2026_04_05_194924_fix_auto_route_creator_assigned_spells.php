<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mapping = [
            78151 => 32182, // Heroism
            19372 => 90355, // Ancient Hysteria
        ];

        foreach ($mapping as $old => $new) {
            DB::update("UPDATE kill_zone_spells SET spell_id = :new WHERE spell_id = :old", ['new' => $new, 'old' => $old]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
