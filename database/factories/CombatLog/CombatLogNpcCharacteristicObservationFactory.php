<?php

namespace Database\Factories\CombatLog;

use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Models\Npc\Npc;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CombatLogNpcCharacteristicObservation>
 */
class CombatLogNpcCharacteristicObservationFactory extends Factory
{
    protected $model = CombatLogNpcCharacteristicObservation::class;

    public function definition(): array
    {
        /** @var Npc $npc */
        $npc = Npc::inRandomOrder()->first();

        /** @var Characteristic $characteristic */
        $characteristic = Characteristic::inRandomOrder()->first();

        return [
            'npc_id'            => $npc->id,
            'characteristic_id' => $characteristic->id,
            'observed_on'       => Carbon::today(),
            'combat_log_path'   => sprintf('factory/%s.log', $this->faker->uuid()),
        ];
    }
}
