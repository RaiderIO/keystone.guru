<?php

use App\Models\Npc\NpcCharacteristic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Staging had 0 duplicate (npc_id, characteristic_id) pairs when this was written, but combat-log
        // workers keep writing between that check and the cron-driven `migrate` run, so dedupe here too -
        // otherwise a race-produced duplicate in that window fails the index creation and blocks every
        // migration behind it. Keeps the lowest id per pair; nothing else references npc_characteristics.id.
        $survivingIds = NpcCharacteristic::query()
            ->selectRaw('MIN(id) as id')
            ->groupBy('npc_id', 'characteristic_id')
            ->pluck('id');

        NpcCharacteristic::query()
            ->whereNotIn('id', $survivingIds)
            ->delete();

        Schema::table('npc_characteristics', function (Blueprint $table) {
            $table->unique(['npc_id', 'characteristic_id'], 'npc_char_npc_char_unique');
        });
    }

    public function down(): void
    {
        Schema::table('npc_characteristics', function (Blueprint $table) {
            $table->dropUnique('npc_char_npc_char_unique');
        });
    }
};
