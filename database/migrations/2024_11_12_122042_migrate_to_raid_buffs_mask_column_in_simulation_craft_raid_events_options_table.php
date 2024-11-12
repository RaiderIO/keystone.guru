<?php

use App\Models\SimulationCraft\SimulationCraftRaidBuffs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $replace = [
            'bloodlust'            => SimulationCraftRaidBuffs::Bloodlust,
            'arcane_intellect'     => SimulationCraftRaidBuffs::ArcaneIntellect,
            'power_word_fortitude' => SimulationCraftRaidBuffs::PowerWordFortitude,
            'battle_shout'         => SimulationCraftRaidBuffs::BattleShout,
            'mystic_touch'         => SimulationCraftRaidBuffs::MysticTouch,
            'chaos_brand'          => SimulationCraftRaidBuffs::ChaosBrand,
        ];

        foreach($replace as $from => $to) {
            /** @var SimulationCraftRaidBuffs $to */
            /** @noinspection SqlResolve */
            DB::update(
                sprintf(
                    'UPDATE `simulation_craft_raid_events_options` SET `raid_buffs_mask` = `raid_buffs_mask` | %d WHERE `%s` = 1',
                    $to->value,
                    $from
                )
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simulation_craft_raid_events_options', function (Blueprint $table) {
            //
        });
    }
};
