<?php

use App\Models\Affix;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CombatLogEventFactory extends Factory
{
    protected $model = CombatLogEvent::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $runDurationMin = $this->faker->numberBetween(15, 45);
        $runAgeMin      = 5;

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::with(['currentMappingVersion'])
            ->where('expansion_id', Expansion::where('shortname', Expansion::EXPANSION_DRAGONFLIGHT)->first()->id)
            ->inRandomOrder()
            ->first();

        /** @var Floor $floor */
        $floor = $dungeon->floors->first();

        return [
            '@timestamp'        => Carbon::now()->unix(),
            'id'                => CombatLogEvent::generateId(),
            'run_id'            => sprintf(
                'season-df-2 - logged: #%d - run: #%d',
                $this->faker->numberBetween(100000, 1000000),
                $this->faker->numberBetween(1000000, 10000000),
            ),
            'challenge_mode_id' => $dungeon->challenge_mode_id,
            'level'             => $this->faker->numberBetween(
                config('keystoneguru.keystone.levels.min'),
                config('keystoneguru.keystone.levels.max'),
            ),
            'affix_id'          => Affix::inRandomOrder()->limit(3)->get()->pluck('id'),
            'success'           => $dungeon->currentMappingVersion->timer_max_seconds > $runDurationMin * 60,
            'start'             => Carbon::now()->subMinutes($runDurationMin + $runAgeMin)->unix(),
            'end'               => Carbon::now()->subMinutes($runAgeMin)->unix(),
            'duration_ms'       => $runDurationMin * 60 * 1000,
            'pos_x'             => $this->faker->numberBetween($floor->ingame_min_x, $floor->ingame_max_x),
            'pos_y'             => $this->faker->numberBetween($floor->ingame_min_y, $floor->ingame_max_y),
            'event_type'        => CombatLogEvent::ALL_EVENT_TYPE[$this->faker->numberBetween(0, count(CombatLogEvent::ALL_EVENT_TYPE))],
            'characters'        => [],
            'context'           => [],
        ];
    }
}
