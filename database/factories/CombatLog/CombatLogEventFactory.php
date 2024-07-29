<?php

namespace Database\Factories\CombatLog;

use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CombatLogEventFactory extends Factory
{
    protected $model = CombatLogEvent::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        /** @var Expansion $expansion */
        $expansion = Expansion::where('shortname', Expansion::EXPANSION_DRAGONFLIGHT)->first();

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::with(['currentMappingVersion'])
            ->where('expansion_id', $expansion->id)
            ->inRandomOrder()
            ->first();

        /** @var Season $season */
        $season = Season::where('expansion_id', $expansion->id)
            ->inRandomOrder()
            ->first();

        /** @var AffixGroup $affixGroup */
        $affixGroup = $season->affixGroups()
            ->inRandomOrder()
            ->first();

        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->first();

        return $this->definitionFromState(
            $dungeon,
            $floor,
            $affixGroup,
        );
    }

    public function definitionFromState(Dungeon $dungeon, Floor $floor, AffixGroup $affixGroup): array
    {
        $runDurationMin = $this->faker->numberBetween(15, 45);
        $runAgeMin      = 5;

        $now = Carbon::now();

        return [
            '@timestamp'        => $now->unix(),
            'run_id'            => sprintf(
                'season-df-2 - logged: #%d - run: #%d',
                $this->faker->numberBetween(100000, 1000000),
                $this->faker->numberBetween(1000000, 10000000),
            ),
            'challenge_mode_id' => $dungeon->challenge_mode_id,
            'level'             => $this->faker->numberBetween(
                config('keystoneguru.keystone.levels.default_min'),
                config('keystoneguru.keystone.levels.default_max'),
            ),
            'affix_id'          => $affixGroup->affixes->pluck('affix_id')->toArray(),
            'success'           => $dungeon->currentMappingVersion->timer_max_seconds > $runDurationMin * 60,
            'start'             => $now->subMinutes($runDurationMin + $runAgeMin)->unix(),
            'end'               => $now->subMinutes($runAgeMin)->unix(),
            'duration_ms'       => $runDurationMin * 60 * 1000,
            'ui_map_id'         => $floor->ui_map_id,
            'pos_x'             => $this->faker->numberBetween($floor->ingame_min_x, $floor->ingame_max_x),
            'pos_y'             => $this->faker->numberBetween($floor->ingame_min_y, $floor->ingame_max_y),
            'event_type'        => CombatLogEvent::ALL_EVENT_TYPE[$this->faker->numberBetween(0, count(CombatLogEvent::ALL_EVENT_TYPE))],
            'characters'        => [],
            'context'           => [],
        ];
    }

    public function withType(string $type): self
    {
        return $this->state(fn(array $attributes) => [
            'event_type' => $type,
        ]);
    }

    public function withRunId(string $runId): self
    {
        return $this->state(fn(array $attributes) => [
            'run_id' => $runId,
        ]);
    }
}
