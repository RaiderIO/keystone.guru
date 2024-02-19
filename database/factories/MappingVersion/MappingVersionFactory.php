<?php

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class MappingVersionFactory extends Factory
{
    protected $model = MappingVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::with('currentMappingVersion')->inRandomOrder()->first();

        return [
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $dungeon->currentMappingVersion->version + 1,
            'enemy_forces_required'           => 350,
            'enemy_forces_required_teeming'   => null,
            'enemy_forces_shrouded'           => 9,
            'enemy_forces_shrouded_zul_gamux' => 27,
            'timer_max_seconds'               => 1800,
            'mdt_mapping_hash'                => null,
        ];
    }
}
