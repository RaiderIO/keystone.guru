<?php

namespace Tests\Feature\App\Service\Npc;

use App\Models\CombatLog\CombatLogEncounterObservation;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEncounterMechanic;
use App\Models\Npc\NpcEncounterMechanicType;
use App\Models\Npc\NpcHealth;
use App\Service\CombatLog\DataExtractors\EncounterMechanics\EncounterObservation;
use App\Service\Npc\Dtos\NpcEncounterMechanicBuildResult;
use App\Service\Npc\NpcEncounterMechanicServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('EncounterMechanics')]
final class NpcEncounterMechanicServiceTest extends PublicTestCase
{
    use CreatesNpc;
    use ProvidesDungeon;

    private const int RUN_COUNT = 5;

    /** Light Bolt, cast all through the Trinity fight */
    private const int FILLER_SPELL_ID = 1235616;

    /** Bedrock Slam, which moves the party */
    private const int SLAM_SPELL_ID = 1234753;

    /** Raging Squall, cast twice 3 s apart every cycle */
    private const int SQUALL_SPELL_ID = 1235623;

    private Dungeon $dungeon;

    private int $encounterId;

    private int $runId = 0;

    /** @var list<NpcHealth> */
    private array $createdNpcHealths = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'keystoneguru.combat_log_encounter_observations.mechanics.min_runs'         => 5,
            'keystoneguru.combat_log_encounter_observations.mechanics.recurrence_ratio' => 0.6,
        ]);

        [$this->dungeon]   = $this->findDungeon();
        $this->encounterId = random_int(900_000_000, 999_999_999);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogEncounterObservation::query()->where('encounter_id', $this->encounterId)->delete();
            NpcEncounterMechanic::query()->where('encounter_id', $this->encounterId)->delete();
            foreach ($this->createdNpcHealths as $npcHealth) {
                $npcHealth->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function buildEncounter_givenAHostileBossInEveryKill_writesABossRow(): void
    {
        // Arrange
        $boss  = $this->createBoss();
        $trash = $this->createNpcInDatabase();
        $this->observeRuns(fn() => ['npcs' => [$boss->id => $this->npc(), $trash->id => $this->npc()]]);

        // Act
        $result = $this->buildEncounter();

        // Assert
        $rows = $this->getRows();
        $this->assertSame(['boss' => 1], $result->rowCountsByType);
        $this->assertCount(1, $rows);
        $this->assertSame($boss->id, $rows->first()->npc_id);
        $this->assertSame(self::RUN_COUNT, $rows->first()->run_count);
        $this->assertSame(self::RUN_COUNT, $rows->first()->total_run_count);
    }

    #[Test]
    public function buildEncounter_givenNoBossClassifiedNpc_writesNothing(): void
    {
        // Arrange
        $trash = $this->createNpcInDatabase();
        $add   = $this->createNpcInDatabase();
        $this->observeRuns(fn() => ['npcs' => [$trash->id => $this->npc(), $add->id => $this->npc(waves: [[30_000, 2, 70.0]])]]);

        // Act
        $result = $this->buildEncounter();

        // Assert
        $this->assertSame([], $result->rowCountsByType);
        $this->assertCount(0, $this->getRows());
    }

    #[Test]
    public function buildEncounter_givenABossThatIsFriendlyInMostKills_writesNothing(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $this->observeRuns(fn(int $run) => ['npcs' => [$boss->id => $this->npc(reaction: $run < 3 ? EncounterObservation::REACTION_FRIENDLY : EncounterObservation::REACTION_HOSTILE)]]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertCount(0, $this->getRows());
    }

    #[Test]
    public function buildEncounter_givenFewerKillsThanMinRuns_keepsTheExistingRows(): void
    {
        // Arrange
        $boss     = $this->createBoss();
        $existing = NpcEncounterMechanic::factory()->create([
            'dungeon_id'   => $this->dungeon->id,
            'encounter_id' => $this->encounterId,
        ]);
        $this->observeRuns(fn() => ['npcs' => [$boss->id => $this->npc()]], self::RUN_COUNT - 1);

        // Act
        $result = $this->buildEncounter();

        // Assert
        $this->assertNull($result->rowCountsByType);
        $this->assertSame(self::RUN_COUNT - 1, $result->runCount);
        $this->assertSame([$existing->id], $this->getRows()->pluck('id')->all());
    }

    #[Test]
    public function buildEncounter_givenWipes_leavesThemOut(): void
    {
        // Arrange
        $boss     = $this->createBoss();
        $existing = NpcEncounterMechanic::factory()->create([
            'dungeon_id'   => $this->dungeon->id,
            'encounter_id' => $this->encounterId,
        ]);
        $this->observeRuns(fn() => ['npcs' => [$boss->id => $this->npc()]], self::RUN_COUNT - 1);
        $this->observeRuns(fn() => ['npcs' => [$boss->id => $this->npc()]], 3, ['success' => false]);

        // Act
        $result = $this->buildEncounter();

        // Assert
        $this->assertSame(self::RUN_COUNT - 1, $result->runCount);
        $this->assertSame([$existing->id], $this->getRows()->pluck('id')->all());
    }

    #[Test]
    public function buildEncounter_givenSeveralKillsOfOneRun_countsTheRunOnceWithItsLatestKill(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $add  = $this->createNpcInDatabase();
        // Three runs show the add; two of them again in a second file that does not, which is the one that counts
        $this->observeRuns(fn() => ['npcs' => [$boss->id => $this->npc(), $add->id => $this->npc(waves: [[30_000, 1, 70.0]])]], 3);
        $this->observeRuns(fn() => ['npcs' => [$boss->id => $this->npc()]], 2);
        $this->createObservation(['npcs' => [$boss->id => $this->npc()]], ['run_key' => sprintf('run:%d', 1)]);
        $this->createObservation(['npcs' => [$boss->id => $this->npc()]], ['run_key' => sprintf('run:%d', 2)]);

        // Act
        $result = $this->buildEncounter();

        // Assert
        $this->assertSame(self::RUN_COUNT, $result->runCount);
        $this->assertSame(['boss' => 1], $result->rowCountsByType);
    }

    #[Test]
    public function buildEncounter_givenExistingRows_replacesThemButLeavesOtherEncountersAlone(): void
    {
        // Arrange
        $boss     = $this->createBoss();
        $existing = NpcEncounterMechanic::factory()->create([
            'dungeon_id'   => $this->dungeon->id,
            'encounter_id' => $this->encounterId,
        ]);
        $otherEncounter = NpcEncounterMechanic::factory()->create([
            'dungeon_id'   => $this->dungeon->id,
            'encounter_id' => $this->encounterId + 1,
        ]);
        $this->observeRuns(fn() => ['npcs' => [$boss->id => $this->npc()]]);

        try {
            // Act
            $this->buildEncounter();
            $this->buildEncounter();

            // Assert
            $rows = $this->getRows();
            $this->assertCount(1, $rows);
            $this->assertNotSame($existing->id, $rows->first()->id);
            $this->assertSame(NpcEncounterMechanicType::Boss, $rows->first()->type);
            $this->assertTrue(NpcEncounterMechanic::query()->whereKey($otherEncounter->id)->exists());
        } finally {
            $otherEncounter->delete();
        }
    }

    #[Test]
    public function buildEncounter_givenAddsInWavesAtAgreeingTimes_writesAnAddsRow(): void
    {
        // Arrange
        $boss       = $this->createBoss();
        $add        = $this->createNpcInDatabase();
        $firstTimes = [29_000, 30_000, 30_000, 31_000, 30_500];
        $this->observeRuns(fn(int $run) => ['npcs' => [
            $boss->id => $this->npc(),
            $add->id  => $this->npc(
                waves: [[$firstTimes[$run], 2, 72.0], [$firstTimes[$run] + 63_000, 2, 40.0]],
                summonedBy: $boss->id,
                lifetimes: [10_000 + $run * 1_000],
            ),
        ]]);

        // Act
        $this->buildEncounter();

        // Assert
        $row = $this->getRow(NpcEncounterMechanicType::Adds);
        $this->assertSame($add->id, $row->npc_id);
        $this->assertSame(30_000, $row->first_ms);
        $this->assertNull($row->first_health_pct);
        $this->assertSame(63_000, $row->cooldown_ms);
        $this->assertSame(2, $row->count);
        $this->assertSame(12_000, $row->duration_ms);
        $this->assertSame(self::RUN_COUNT, $row->run_count);
        $this->assertSame(['attackable' => true, 'summoned_by_boss' => true], $row->data);
    }

    #[Test]
    public function buildEncounter_givenAddsOfOneWaveInMostKillsThatNoPlayerAttacks_writesAnAddsRowWithoutCooldown(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $add  = $this->createNpcInDatabase();
        // Two of five runs last long enough for a second wave; a typical run has one
        $this->observeRuns(fn(int $run) => ['npcs' => [
            $boss->id => $this->npc(),
            $add->id  => $this->npc(waves: $run < 2 ? [[10_000, 5, 90.0], [70_000, 5, 40.0]] : [[10_000, 5, 90.0]], damage: 0),
        ]]);

        // Act
        $this->buildEncounter();

        // Assert
        $row = $this->getRow(NpcEncounterMechanicType::Adds);
        $this->assertNull($row->cooldown_ms);
        $this->assertNull($row->duration_ms);
        $this->assertSame(5, $row->count);
        $this->assertSame(['attackable' => false, 'summoned_by_boss' => false], $row->data);
    }

    #[Test]
    public function buildEncounter_givenAddsInTooFewKills_writesNoAddsRow(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $add  = $this->createNpcInDatabase();
        $this->observeRuns(fn(int $run) => ['npcs' => $run < 2
            ? [$boss->id => $this->npc(), $add->id => $this->npc(waves: [[30_000, 1, 70.0]])]
            : [$boss->id => $this->npc()]]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertNull($this->findRow(NpcEncounterMechanicType::Adds));
    }

    #[Test]
    public function buildEncounter_givenUnitsThereFromThePull_writesNoAddsRow(): void
    {
        // Arrange
        $boss  = $this->createBoss();
        $trash = $this->createNpcInDatabase();
        $this->observeRuns(fn() => ['npcs' => [$boss->id => $this->npc(), $trash->id => $this->npc(waves: [[2_000, 3, 100.0]])]]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertNull($this->findRow(NpcEncounterMechanicType::Adds));
    }

    #[Test]
    public function buildEncounter_givenUnitsThatNeverShowTheirHealth_writesNoAddsRow(): void
    {
        // Arrange
        $boss   = $this->createBoss();
        $helper = $this->createNpcInDatabase();
        $this->observeRuns(fn() => ['npcs' => [$boss->id => $this->npc(), $helper->id => $this->npc(maxHp: null, waves: [[30_000, 1, 70.0]], damage: 0)]]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertNull($this->findRow(NpcEncounterMechanicType::Adds));
    }

    #[Test]
    public function buildEncounter_givenAddsAtDisagreeingTimesAndHealth_writesNoAddsRow(): void
    {
        // Arrange
        $boss        = $this->createBoss();
        $add         = $this->createNpcInDatabase();
        $firstTimes  = [20_000, 40_000, 60_000, 80_000, 100_000];
        $firstHealth = [90.0, 70.0, 50.0, 30.0, 10.0];
        $this->observeRuns(fn(int $run) => ['npcs' => [
            $boss->id => $this->npc(),
            $add->id  => $this->npc(waves: [[$firstTimes[$run], 1, $firstHealth[$run]]]),
        ]]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertNull($this->findRow(NpcEncounterMechanicType::Adds));
    }

    #[Test]
    public function buildEncounter_givenAddsAtDisagreeingTimesButAgreeingHealth_writesAHealthTriggeredAddsRow(): void
    {
        // Arrange
        $boss        = $this->createBoss();
        $add         = $this->createNpcInDatabase();
        $firstTimes  = [20_000, 40_000, 60_000, 80_000, 100_000];
        $firstHealth = [62.0, 60.0, 58.0, 61.0, 30.0];
        $this->observeRuns(fn(int $run) => ['npcs' => [
            $boss->id => $this->npc(),
            $add->id  => $this->npc(waves: [[$firstTimes[$run], 1, $firstHealth[$run]]]),
        ]]);

        // Act
        $this->buildEncounter();

        // Assert
        $row = $this->getRow(NpcEncounterMechanicType::Adds);
        $this->assertNull($row->first_ms);
        $this->assertSame(60.0, $row->first_health_pct);
    }

    #[Test]
    public function buildEncounter_givenBossNoDamageWindowsWithAnAura_writesAnInvulnerableRow(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $this->observeRuns(fn(int $run) => [
            'npcs'              => [$boss->id => $this->npc()],
            'no_damage_windows' => [
                $this->window($boss->id, 26_000 + $run * 100, 8_000, 80.0, [1298223, 1298221]),
                $this->window($boss->id, 81_000 + $run * 100, 7_000, 50.0, [1298221, 1298223]),
                $this->window($boss->id, 136_000, 9_000, 20.0, [1298221, 55555]),
                // The kill itself: below 2% health it is no intermission
                $this->window($boss->id, 190_000, 15_000, 0.4, [55555, 1298221]),
            ],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $row = $this->getRow(NpcEncounterMechanicType::Invulnerable);
        $this->assertSame($boss->id, $row->npc_id);
        $this->assertSame(1298221, $row->spell_id);
        $this->assertSame(26_200, $row->first_ms);
        $this->assertSame(55_000, $row->cooldown_ms);
        $this->assertSame(8_000, $row->duration_ms);
        $this->assertSame(self::RUN_COUNT, $row->run_count);
    }

    #[Test]
    public function buildEncounter_givenNoDamageWindowsAtDisagreeingTimes_writesNoInvulnerableRow(): void
    {
        // Arrange
        $boss       = $this->createBoss();
        $firstTimes = [20_000, 40_000, 60_000, 80_000, 100_000];
        $this->observeRuns(fn(int $run) => [
            'npcs'              => [$boss->id => $this->npc()],
            'no_damage_windows' => [$this->window($boss->id, $firstTimes[$run], 8_000, 80.0, [1298221])],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertNull($this->findRow(NpcEncounterMechanicType::Invulnerable));
    }

    #[Test]
    public function buildEncounter_givenNoDamageWindowsInTooFewKillsOrOnTrash_writesNoInvulnerableRow(): void
    {
        // Arrange
        $boss  = $this->createBoss();
        $trash = $this->createNpcInDatabase();
        $this->observeRuns(fn(int $run) => [
            'npcs'              => [$boss->id => $this->npc(), $trash->id => $this->npc()],
            'no_damage_windows' => array_merge(
                $run < 2 ? [$this->window($boss->id, 26_000, 8_000, 80.0, [1298221])] : [],
                [$this->window($trash->id, 26_000, 8_000, 80.0, [1298221])],
            ),
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertNull($this->findRow(NpcEncounterMechanicType::Invulnerable));
    }

    #[Test]
    public function buildEncounter_givenBossesSharingHealthInMostKills_writesASharedHealthRow(): void
    {
        // Arrange
        $bossA = $this->createBoss();
        $bossB = $this->createBoss();
        $trash = $this->createNpcInDatabase();
        $this->observeRuns(fn(int $run) => [
            'npcs'          => [$bossA->id => $this->npc(), $bossB->id => $this->npc(), $trash->id => $this->npc()],
            'shared_health' => $run < 3
                ? [[$bossB->id, $bossA->id, $bossA->id], [$trash->id, $trash->id]]
                : [[$trash->id, $trash->id]],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $rows = $this->getRows()->filter(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::SharedHealth);
        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertSame($bossA->id, $row->npc_id);
        $this->assertSame(3, $row->count);
        $this->assertSame(3, $row->run_count);
        $this->assertSame(['npc_ids' => [$bossA->id, $bossB->id]], $row->data);
    }

    #[Test]
    public function buildEncounter_givenBossesSharingHealthInTooFewKills_writesNoSharedHealthRow(): void
    {
        // Arrange
        $bossA = $this->createBoss();
        $bossB = $this->createBoss();
        $this->observeRuns(fn(int $run) => [
            'npcs'          => [$bossA->id => $this->npc(), $bossB->id => $this->npc()],
            'shared_health' => $run < 2 ? [[$bossA->id, $bossB->id]] : [],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertNull($this->findRow(NpcEncounterMechanicType::SharedHealth));
    }

    #[Test]
    public function buildEncounter_givenPartyMovementWithoutCastCounts_givesTheWindowsToTheSpellInMostOfThem(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $this->observeRuns(fn(int $run) => [
            'npcs'             => [$boss->id => $this->npc()],
            'movement_windows' => [
                // The pull itself
                $this->movementWindow(1_000, 3_000, [1222100]),
                $this->movementWindow(15_000 + $run * 100, 4_000, [1222100, 777]),
                $this->movementWindow(50_000, 4_000, [1222100]),
                $this->movementWindow(85_000, 4_000, [1222100, 777]),
                // 777 recurs as well, but only in windows Nether Dash already took
                $this->movementWindow(100_000, 3_000, $run < 2 ? [888] : []),
            ],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $rows = $this->getRows()->filter(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::Movement);
        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertSame(1222100, $row->spell_id);
        $this->assertSame(15_200, $row->first_ms);
        $this->assertSame(35_000, $row->cooldown_ms);
        $this->assertSame(4_000, $row->duration_ms);
        $this->assertEquals(['median_distance' => 16.0, 'median_casts_per_run' => null], $row->data);
    }

    #[Test]
    public function buildEncounter_givenPartyMovementAtDisagreeingTimes_writesNoMovementRow(): void
    {
        // Arrange
        $boss       = $this->createBoss();
        $firstTimes = [20_000, 40_000, 60_000, 80_000, 100_000];
        $this->observeRuns(fn(int $run) => [
            'npcs'             => [$boss->id => $this->npc()],
            'movement_windows' => [$this->movementWindow($firstTimes[$run], 4_000, [1222100])],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertNull($this->findRow(NpcEncounterMechanicType::Movement));
    }

    #[Test]
    public function buildEncounter_givenAFillerSpellInEveryMovementWindow_givesTheWindowsToTheSpellCastOncePerWindow(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $this->observeRuns(fn(int $run) => [
            'npcs'             => [$boss->id => $this->npc()],
            'movement_windows' => [
                $this->movementWindow(15_000 + $run * 100, 4_000, [self::FILLER_SPELL_ID, self::SLAM_SPELL_ID]),
                $this->movementWindow(50_000, 4_000, [self::FILLER_SPELL_ID, self::SLAM_SPELL_ID]),
                $this->movementWindow(85_000, 4_000, [self::FILLER_SPELL_ID, self::SLAM_SPELL_ID]),
                $this->movementWindow(120_000, 4_000, [self::FILLER_SPELL_ID]),
            ],
            'cast_counts' => [self::FILLER_SPELL_ID => 8, self::SLAM_SPELL_ID => 3],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $rows = $this->getRows()->filter(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::Movement);
        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertSame(self::SLAM_SPELL_ID, $row->spell_id);
        $this->assertSame(15_200, $row->first_ms);
        $this->assertSame(35_000, $row->cooldown_ms);
        $this->assertEquals(['median_distance' => 16.0, 'median_casts_per_run' => 3.0], $row->data);
    }

    #[Test]
    public function buildEncounter_givenASpellCastMoreThanThreeTimesPerMovementWindow_writesNoMovementRowForIt(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $this->observeRuns(fn(int $run) => [
            'npcs'             => [$boss->id => $this->npc()],
            'movement_windows' => [
                $this->movementWindow(15_000, 4_000, [self::FILLER_SPELL_ID]),
                $this->movementWindow(20_000, 4_000, [self::SLAM_SPELL_ID]),
                $this->movementWindow(50_000, 4_000, [self::FILLER_SPELL_ID]),
                $this->movementWindow(55_000, 4_000, [self::SLAM_SPELL_ID]),
                $this->movementWindow(85_000, 4_000, [self::FILLER_SPELL_ID]),
                $this->movementWindow(90_000, 4_000, [self::SLAM_SPELL_ID]),
            ],
            // Three windows each: ten casts is above the ratio, nine is exactly at it
            'cast_counts' => [self::FILLER_SPELL_ID => 10, self::SLAM_SPELL_ID => 9],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $rows = $this->getRows()->filter(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::Movement);
        $this->assertSame([self::SLAM_SPELL_ID], $rows->pluck('spell_id')->values()->all());
        $this->assertSame(20_000, $rows->first()->first_ms);
    }

    #[Test]
    public function buildEncounter_givenMovementWindowsOfOneSpellSecondsApart_countsThemAsOneOccurrence(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $this->observeRuns(fn(int $run) => [
            'npcs'             => [$boss->id => $this->npc()],
            'movement_windows' => [
                $this->movementWindow(14_000, 3_000, [self::SQUALL_SPELL_ID]),
                $this->movementWindow(18_000, 3_000, [self::SQUALL_SPELL_ID]),
                $this->movementWindow(77_000, 3_000, [self::SQUALL_SPELL_ID]),
                $this->movementWindow(140_000, 3_000, [self::SQUALL_SPELL_ID]),
                $this->movementWindow(143_000, 3_000, [self::SQUALL_SPELL_ID]),
            ],
            'cast_counts' => [self::SQUALL_SPELL_ID => 3],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $row = $this->findRow(NpcEncounterMechanicType::Movement);
        $this->assertNotNull($row);
        $this->assertSame(self::SQUALL_SPELL_ID, $row->spell_id);
        $this->assertSame(14_000, $row->first_ms);
        $this->assertSame(63_000, $row->cooldown_ms);
        $this->assertSame(6_000, $row->duration_ms);
    }

    #[Test]
    public function buildEncounter_givenASpellCastMoreThanThreeTimesPerMergedOccurrence_writesNoMovementRowForIt(): void
    {
        // Arrange
        $boss = $this->createBoss();
        $this->observeRuns(fn(int $run) => [
            'npcs'             => [$boss->id => $this->npc()],
            'movement_windows' => [
                // Six windows, but three occurrences: ten casts is below three per window and above three per occurrence
                $this->movementWindow(20_000, 3_000, [self::FILLER_SPELL_ID]),
                $this->movementWindow(23_000, 3_000, [self::FILLER_SPELL_ID]),
                $this->movementWindow(40_000, 3_000, [self::SLAM_SPELL_ID]),
                $this->movementWindow(60_000, 3_000, [self::FILLER_SPELL_ID]),
                $this->movementWindow(63_000, 3_000, [self::FILLER_SPELL_ID]),
                $this->movementWindow(80_000, 3_000, [self::SLAM_SPELL_ID]),
                $this->movementWindow(100_000, 3_000, [self::FILLER_SPELL_ID]),
                $this->movementWindow(103_000, 3_000, [self::FILLER_SPELL_ID]),
                $this->movementWindow(120_000, 3_000, [self::SLAM_SPELL_ID]),
            ],
            'cast_counts' => [self::FILLER_SPELL_ID => 10, self::SLAM_SPELL_ID => 3],
        ]);

        // Act
        $this->buildEncounter();

        // Assert
        $rows = $this->getRows()->filter(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::Movement);
        $this->assertSame([self::SLAM_SPELL_ID], $rows->pluck('spell_id')->values()->all());
    }

    #[Test]
    public function buildEncounter_givenAFriendlyNpcWithHealth_writesAFriendlyRow(): void
    {
        // Arrange
        $boss          = $this->createBoss();
        $friendly      = $this->createNpcInDatabase();
        $healthless    = $this->createNpcInDatabase();
        $mostlyHostile = $this->createNpcInDatabase();
        $this->observeRuns(fn(int $run) => ['npcs' => [
            $boss->id          => $this->npc(),
            $friendly->id      => $this->npc(reaction: EncounterObservation::REACTION_FRIENDLY, waves: [[0, 1, 100.0]], damage: 0),
            $healthless->id    => $this->npc(reaction: EncounterObservation::REACTION_FRIENDLY, maxHp: null, waves: [[0, 1, 100.0]], damage: 0),
            $mostlyHostile->id => $this->npc(reaction: $run < 2 ? EncounterObservation::REACTION_FRIENDLY : EncounterObservation::REACTION_HOSTILE, waves: [[0, 1, 100.0]]),
        ]]);

        // Act
        $this->buildEncounter();

        // Assert
        $rows = $this->getRows()->filter(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::Friendly);
        $this->assertSame([$friendly->id], $rows->pluck('npc_id')->values()->all());
    }

    #[Test]
    public function buildEncounter_givenAnNpcAtItsBaseHealthTimesTheKeyLevelFactor_writesAnAffixExemptRow(): void
    {
        // Arrange
        $boss      = $this->createBoss();
        $exempt    = $this->createNpcWithBaseHealth(810_814);
        $fortified = $this->createNpcWithBaseHealth(810_814);
        $keyLevels = [16, 16, 15, 12, 9];
        $this->observeRuns(fn(int $run) => ['npcs' => [
            $boss->id      => $this->npc(),
            $exempt->id    => $this->npc(maxHp: (int)round(810_814 * Npc::getKeyLevelScalingFactor($keyLevels[$run]))),
            $fortified->id => $this->npc(maxHp: (int)round(810_814 * Npc::getKeyLevelScalingFactor($keyLevels[$run]) * 1.2)),
        ]], attributes: fn(int $run) => ['key_level' => $keyLevels[$run]]);

        // Act
        $this->buildEncounter();

        // Assert
        $rows = $this->getRows()->filter(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::AffixExempt);
        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertSame($exempt->id, $row->npc_id);
        $this->assertSame(4, $row->run_count);
        $this->assertEquals(['ratio' => 1.0, 'key_level_run_count' => 4], $row->data);
    }

    #[Test]
    public function buildEncounter_givenAnNpcAtItsBaseHealthOnlyInKeysWithoutBothAffixes_writesNoAffixExemptRow(): void
    {
        // Arrange
        $boss   = $this->createBoss();
        $exempt = $this->createNpcWithBaseHealth(810_814);
        $this->observeRuns(fn() => ['npcs' => [
            $boss->id   => $this->npc(),
            $exempt->id => $this->npc(maxHp: (int)round(810_814 * Npc::getKeyLevelScalingFactor(9))),
        ]], attributes: fn() => ['key_level' => 9]);

        // Act
        $this->buildEncounter();

        // Assert
        $this->assertNull($this->findRow(NpcEncounterMechanicType::AffixExempt));
    }

    private function buildEncounter(): NpcEncounterMechanicBuildResult
    {
        return app(NpcEncounterMechanicServiceInterface::class)->buildEncounter($this->dungeon->id, $this->encounterId);
    }

    private function createBoss(): Npc
    {
        return $this->createNpcInDatabase(['classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]]);
    }

    private function createNpcWithBaseHealth(int $health): Npc
    {
        $npc = $this->createNpcInDatabase();

        $this->createdNpcHealths[] = NpcHealth::query()->create([
            'npc_id'          => $npc->id,
            'game_version_id' => $this->dungeon->getCurrentMappingVersion()->game_version_id,
            'health'          => $health,
            'percentage'      => null,
        ]);

        return $npc;
    }

    /**
     * One kill per run, each run its own.
     *
     * @param callable(int): array<string, mixed>                        $data       the run's index => its observation data
     * @param (callable(int): array<string, mixed>)|array<string, mixed> $attributes
     */
    private function observeRuns(callable $data, int $runCount = self::RUN_COUNT, callable|array $attributes = []): void
    {
        for ($run = 0; $run < $runCount; $run++) {
            $this->createObservation($data($run), is_callable($attributes) ? $attributes($run) : $attributes);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $attributes
     */
    private function createObservation(array $data, array $attributes = []): void
    {
        $this->runId++;

        CombatLogEncounterObservation::factory()->create(array_merge([
            'dungeon_id'   => $this->dungeon->id,
            'encounter_id' => $this->encounterId,
            'run_key'      => sprintf('run:%d', $this->runId),
            'key_level'    => 16,
            'success'      => true,
            'data'         => array_merge(['npcs' => [], 'no_damage_windows' => [], 'shared_health' => [], 'movement_windows' => []], $data),
        ], $attributes));
    }

    /**
     * @param  list<array{int, int, float|null}> $waves     [time, count, boss health %]
     * @param  list<int>                         $lifetimes
     * @return array<string, mixed>
     */
    private function npc(
        string $reaction = EncounterObservation::REACTION_HOSTILE,
        ?int   $maxHp = 1_000_000,
        array  $waves = [[0, 1, 100.0]],
        int    $damage = 100,
        ?int   $summonedBy = null,
        array  $lifetimes = [],
    ): array {
        return [
            'reaction'             => $reaction,
            'max_hp'               => $maxHp,
            'count'                => array_sum(array_column($waves, 1)),
            'player_damage_events' => $damage,
            'deaths'               => count($lifetimes),
            'summoned_by_npc_id'   => $summonedBy,
            'min_hp_pct'           => null,
            'waves'                => array_map(static fn(array $wave) => ['t' => $wave[0], 'count' => $wave[1], 'boss_hp_pct' => $wave[2]], $waves),
            'lifetimes_ms'         => $lifetimes,
        ];
    }

    /**
     * @param  list<int>            $auraSpellIds
     * @return array<string, mixed>
     */
    private function window(int $npcId, int $t, int $durationMs, ?float $healthPct, array $auraSpellIds): array
    {
        return ['npc_id' => $npcId, 't' => $t, 'duration_ms' => $durationMs, 'hp_pct' => $healthPct, 'aura_spell_ids' => $auraSpellIds];
    }

    /**
     * @param  list<int>            $castSpellIds
     * @return array<string, mixed>
     */
    private function movementWindow(int $t, int $durationMs, array $castSpellIds): array
    {
        return ['t' => $t, 'duration_ms' => $durationMs, 'players' => 5, 'median_distance' => 16.0, 'cast_spell_ids' => $castSpellIds];
    }

    private function getRow(NpcEncounterMechanicType $type): NpcEncounterMechanic
    {
        $row = $this->findRow($type);
        $this->assertNotNull($row, sprintf('Expected a %s row', $type->value));
        $this->assertTrue($this->getRows()->contains(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::Boss));

        return $row;
    }

    private function findRow(NpcEncounterMechanicType $type): ?NpcEncounterMechanic
    {
        return $this->getRows()->first(static fn(NpcEncounterMechanic $row) => $row->type === $type);
    }

    /**
     * @return Collection<int, NpcEncounterMechanic>
     */
    private function getRows(): Collection
    {
        return NpcEncounterMechanic::query()->where('encounter_id', $this->encounterId)->orderBy('id')->get()->toBase();
    }
}
