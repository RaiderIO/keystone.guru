<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Console\Commands\CombatLog\BuildEncounterMechanics;
use App\Models\CombatLog\CombatLogEncounterObservation;
use App\Models\Dungeon;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEncounterMechanic;
use App\Models\Npc\NpcEncounterMechanicType;
use App\Models\Telemetry\TelemetryMetric;
use App\Service\CombatLog\DataExtractors\EncounterMechanics\EncounterObservation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
#[Group('EncounterMechanics')]
final class BuildEncounterMechanicsTest extends PublicTestCase
{
    use CreatesDungeon;
    use CreatesNpc;

    private const int ENCOUNTER_ID = 999_999_001;

    private int $telemetryMetricMaxIdBefore;

    private int $runId = 0;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['keystoneguru.combat_log_encounter_observations.mechanics.min_runs' => 5]);

        $this->telemetryMetricMaxIdBefore = (int)TelemetryMetric::query()->max('id');
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogEncounterObservation::query()->whereIn('encounter_id', [self::ENCOUNTER_ID, self::ENCOUNTER_ID + 1])->delete();
            NpcEncounterMechanic::query()->whereIn('encounter_id', [self::ENCOUNTER_ID, self::ENCOUNTER_ID + 1])->delete();

            // Every run records its own duration through trackTime()
            TelemetryMetric::query()
                ->where('id', '>', $this->telemetryMetricMaxIdBefore)
                ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
                ->where('name', 'combatlog:buildencountermechanics')
                ->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenADungeonKey_buildsOnlyThatDungeonsEncountersAndPrintsASummary(): void
    {
        // Arrange
        $dungeon      = $this->createDungeon();
        $otherDungeon = $this->createDungeon();
        $boss         = $this->createNpcInDatabase(['classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]]);
        $this->observeKills($dungeon, self::ENCOUNTER_ID, $boss->id, 5);
        $this->observeKills($dungeon, self::ENCOUNTER_ID + 1, $boss->id, 2);
        $this->observeKills($otherDungeon, self::ENCOUNTER_ID, $boss->id, 5);

        // Act
        $this->artisan(BuildEncounterMechanics::class, ['--dungeon' => $dungeon->key])
            ->expectsOutput(sprintf('%s encounter %d: 5 kill(s), 1 row(s) (boss 1)', $dungeon->key, self::ENCOUNTER_ID))
            ->expectsOutput(sprintf('%s encounter %d: 2 kill(s), too few - kept its rows', $dungeon->key, self::ENCOUNTER_ID + 1))
            ->assertSuccessful();

        // Assert
        $rows = NpcEncounterMechanic::query()->whereIn('dungeon_id', [$dungeon->id, $otherDungeon->id])->get();
        $this->assertCount(1, $rows);
        $this->assertSame($dungeon->id, $rows->first()->dungeon_id);
        $this->assertSame(self::ENCOUNTER_ID, $rows->first()->encounter_id);
        $this->assertSame(NpcEncounterMechanicType::Boss, $rows->first()->type);
        $this->assertSame($boss->id, $rows->first()->npc_id);
    }

    #[Test]
    public function handle_givenAnUnknownDungeonKey_failsWithoutBuilding(): void
    {
        // Arrange
        $dungeon = $this->createDungeon();
        $boss    = $this->createNpcInDatabase(['classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]]);
        $this->observeKills($dungeon, self::ENCOUNTER_ID, $boss->id, 5);

        // Act
        $this->artisan(BuildEncounterMechanics::class, ['--dungeon' => sprintf('%s_unknown', $dungeon->key)])
            ->expectsOutput(sprintf('Dungeon %s_unknown not found', $dungeon->key))
            ->assertFailed();

        // Assert
        $this->assertFalse(NpcEncounterMechanic::query()->where('dungeon_id', $dungeon->id)->exists());
    }

    private function observeKills(Dungeon $dungeon, int $encounterId, int $bossNpcId, int $killCount): void
    {
        for ($i = 0; $i < $killCount; $i++) {
            $this->runId++;

            CombatLogEncounterObservation::factory()->create([
                'dungeon_id'   => $dungeon->id,
                'encounter_id' => $encounterId,
                'run_key'      => sprintf('run:%d', $this->runId),
                'data'         => [
                    'npcs' => [
                        $bossNpcId => [
                            'reaction'             => EncounterObservation::REACTION_HOSTILE,
                            'max_hp'               => 1_000_000,
                            'count'                => 1,
                            'player_damage_events' => 100,
                            'deaths'               => 1,
                            'summoned_by_npc_id'   => null,
                            'min_hp_pct'           => 0,
                            'waves'                => [['t' => 0, 'count' => 1, 'boss_hp_pct' => 100.0]],
                            'lifetimes_ms'         => [],
                        ],
                    ],
                    'no_damage_windows' => [],
                    'shared_health'     => [],
                    'movement_windows'  => [],
                ],
            ]);
        }
    }
}
