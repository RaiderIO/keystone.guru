<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Bit → property name mapping, mirroring SpellConstants::ALL_MISS_TYPES prefixed with 'miss_'.
     */
    private const array MISS_TYPE_BITS = [
        1   => 'miss_absorb',
        2   => 'miss_block',
        4   => 'miss_deflect',
        8   => 'miss_dodge',
        16  => 'miss_evade',
        32  => 'miss_immune',
        64  => 'miss_miss',
        128 => 'miss_parry',
        256 => 'miss_reflect',
        512 => 'miss_resist',
    ];

    public function up(): void
    {
        $today    = Carbon::today()->toDateString();
        $seedPath = 'seed';
        $now      = Carbon::now()->toDateTimeString();

        // Seed NPC characteristic observations from all existing npc_characteristics rows
        DB::connection('mysql')
            ->table('npc_characteristics')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($today, $seedPath, $now) {
                $insert = [];
                foreach ($rows as $row) {
                    $insert[] = [
                        'npc_id'            => $row->npc_id,
                        'characteristic_id' => $row->characteristic_id,
                        'observed_on'       => $today,
                        'combat_log_path'   => $seedPath,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }

                DB::connection('combatlog')
                    ->table('combat_log_npc_characteristic_observations')
                    ->insertOrIgnore($insert);
            });

        // Seed spell property observations from all existing spells with set properties
        DB::connection('mysql')
            ->table('spells')
            ->where(function ($query) {
                $query->where('aura', true)
                    ->orWhere('debuff', true)
                    ->orWhere('miss_types_mask', '>', 0);
            })
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($today, $seedPath, $now) {
                $insert = [];
                foreach ($rows as $row) {
                    if ($row->aura) {
                        $insert[] = [
                            'spell_id'        => $row->id,
                            'property'        => 'aura',
                            'observed_on'     => $today,
                            'combat_log_path' => $seedPath,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ];
                    }

                    if ($row->debuff) {
                        $insert[] = [
                            'spell_id'        => $row->id,
                            'property'        => 'debuff',
                            'observed_on'     => $today,
                            'combat_log_path' => $seedPath,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ];
                    }

                    foreach (self::MISS_TYPE_BITS as $bit => $property) {
                        if (($row->miss_types_mask & $bit) !== 0) {
                            $insert[] = [
                                'spell_id'        => $row->id,
                                'property'        => $property,
                                'observed_on'     => $today,
                                'combat_log_path' => $seedPath,
                                'created_at'      => $now,
                                'updated_at'      => $now,
                            ];
                        }
                    }
                }

                if (!empty($insert)) {
                    DB::connection('combatlog')
                        ->table('combat_log_spell_property_observations')
                        ->insertOrIgnore($insert);
                }
            });
    }

    public function down(): void
    {
        DB::connection('combatlog')
            ->table('combat_log_npc_characteristic_observations')
            ->where('combat_log_path', 'seed')
            ->delete();

        DB::connection('combatlog')
            ->table('combat_log_spell_property_observations')
            ->where('combat_log_path', 'seed')
            ->delete();
    }
};
