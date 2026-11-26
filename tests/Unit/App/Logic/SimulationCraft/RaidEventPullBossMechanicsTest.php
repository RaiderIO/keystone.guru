<?php

namespace Tests\Unit\App\Logic\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventPull;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEncounterMechanic;
use App\Models\Npc\NpcEncounterMechanicType;
use App\Models\Npc\NpcHealth;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSimulationCraftRaidEventsOptions;
use Tests\TestCase;

#[Group('SimulationCraft')]
final class RaidEventPullBossMechanicsTest extends TestCase
{
    use CreatesSimulationCraftRaidEventsOptions;

    private const int GAME_VERSION_ID = 1;
    private const int PULL_INDEX      = 5;
    private const int ENCOUNTER_ID    = 3208;

    private const int SENTINEL_NPC_ID    = 244100;
    private const int SHIVERCORE_NPC_ID  = 244759;
    private const int SHIVERCORE_HEALTH  = 810814;
    private const int SENTINEL_HEALTH    = 10_000_000;
    private const string SENTINEL_ENTRY  = '"BOSS_sentinel-of-winter_1":40750000';
    private const string SENTINEL_TARGET = 'BOSS_sentinel-of-winter_1';

    /** The Shivercore's health at +16 with Fortified, and with the key level factor (3.26) alone. */
    private const int SHIVERCORE_FORTIFIED_HEALTH    = 3171904;
    private const int SHIVERCORE_AFFIX_EXEMPT_HEALTH = 2643254;

    #[Test]
    public function toString_givenAddsRow_appendsAnAddsEventAfterThePull(): void
    {
        // Arrange
        $sentinel      = $this->makeNpc(self::SENTINEL_NPC_ID, 'Sentinel of Winter', true, self::SENTINEL_HEALTH);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, self::SENTINEL_NPC_ID),
            $this->makeAddsMechanic($this->makeShivercore(), ['count' => 2]),
        ]), $sentinel);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertSame(implode(PHP_EOL, [
            sprintf('raid_events+=/pull,pull=05,bloodlust=0,delay=000,enemies=%s', self::SENTINEL_ENTRY),
            sprintf(
                'raid_events+=/adds,pull=05,name=Adds_05_fractured-shivercore,count=2,health=%d,first=29.4,cooldown=63.2,duration=11.2,force_stop=1',
                self::SHIVERCORE_FORTIFIED_HEALTH,
            ),
        ]), $result);
    }

    #[Test]
    #[DataProvider('addsDurationProvider')]
    public function toString_givenAddsLifetimeAndCooldown_emitsTheLifetimeCappedAtTheCooldown(?int $durationMs, string $expectedDuration): void
    {
        // Arrange
        $sentinel      = $this->makeNpc(self::SENTINEL_NPC_ID, 'Sentinel of Winter', true, self::SENTINEL_HEALTH);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, self::SENTINEL_NPC_ID),
            $this->makeAddsMechanic($this->makeShivercore(), ['duration_ms' => $durationMs, 'cooldown_ms' => 63_177]),
        ]), $sentinel);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertStringContainsString(sprintf(',cooldown=63.2,duration=%s,force_stop=1', $expectedDuration), $result);
    }

    /**
     * @return array<string, array{0: int|null, 1: string}>
     */
    public static function addsDurationProvider(): array
    {
        return [
            'lifetime shorter than the cooldown' => [11_233, '11.2'],
            'lifetime longer than the cooldown'  => [70_000, '63.2'],
            'no add ever died'                   => [null, '63.2'],
        ];
    }

    #[Test]
    public function toString_givenAddsWithoutCooldown_emitsThemOnceAtTheirTimestamp(): void
    {
        // Arrange
        $sentinel      = $this->makeNpc(self::SENTINEL_NPC_ID, 'Sentinel of Winter', true, self::SENTINEL_HEALTH);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, self::SENTINEL_NPC_ID),
            $this->makeAddsMechanic($this->makeShivercore(), ['first_ms' => 42_032, 'cooldown_ms' => null, 'duration_ms' => 9_256]),
        ]), $sentinel);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertStringEndsWith(
            sprintf(
                'raid_events+=/adds,pull=05,name=Adds_05_fractured-shivercore,count=2,health=%d,timestamps=42.0,duration=9.3,force_stop=1',
                self::SHIVERCORE_FORTIFIED_HEALTH,
            ),
            $result,
        );
    }

    #[Test]
    public function toString_givenAddsNoPlayerAttacksOrThatCannotBeTimed_leavesThemOut(): void
    {
        // Arrange - only the first of these adds can be simulated
        $sentinel      = $this->makeNpc(self::SENTINEL_NPC_ID, 'Sentinel of Winter', true, self::SENTINEL_HEALTH);
        $withoutHealth = $this->makeNpc(900_000_003, 'Healthless Add', false, self::SHIVERCORE_HEALTH);
        $withoutHealth->setRelation('npcHealths', new EloquentCollection());
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, self::SENTINEL_NPC_ID),
            $this->makeAddsMechanic($this->makeShivercore()),
            $this->makeAddsMechanic($this->makeNpc(900_000_001, 'Spirit Bear', false, self::SHIVERCORE_HEALTH), ['data' => ['attackable' => false]]),
            $this->makeAddsMechanic($this->makeNpc(900_000_002, 'Immortal Add', false, self::SHIVERCORE_HEALTH), ['cooldown_ms' => null, 'duration_ms' => null]),
            $this->makeAddsMechanic($withoutHealth),
        ]), $sentinel);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertSame(['Adds_05_fractured-shivercore'], $this->getAddsNames($result));
    }

    #[Test]
    public function toString_givenHealthTriggeredAdds_emitsFirstPctAgainstTheBossOfThePull(): void
    {
        // Arrange - a health trigger without a cooldown cannot be said in SimulationCraft, so that one is left out
        $sentinel      = $this->makeNpc(self::SENTINEL_NPC_ID, 'Sentinel of Winter', true, self::SENTINEL_HEALTH);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, self::SENTINEL_NPC_ID),
            $this->makeAddsMechanic($this->makeShivercore(), [
                'first_ms'         => null,
                'first_health_pct' => 50.0,
                'cooldown_ms'      => 30_000,
                'duration_ms'      => 5_000,
            ]),
            $this->makeAddsMechanic($this->makeNpc(900_000_001, 'Once Add', false, self::SHIVERCORE_HEALTH), [
                'first_ms'         => null,
                'first_health_pct' => 30.0,
                'cooldown_ms'      => null,
                'duration_ms'      => 5_000,
            ]),
        ]), $this->makeNpc(900_000_009, 'Trash', false, 1_000), $sentinel);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertStringEndsWith(
            sprintf(
                'raid_events+=/adds,pull=05,name=Adds_05_fractured-shivercore,count=2,health=%d,first_pct=50.0,cooldown=30.0,pull_target=%s,duration=5.0,force_stop=1',
                self::SHIVERCORE_FORTIFIED_HEALTH,
                self::SENTINEL_TARGET,
            ),
            $result,
        );
        Assert::assertSame(['Adds_05_fractured-shivercore'], $this->getAddsNames($result));
    }

    #[Test]
    public function toString_givenInvulnerableRow_emitsAnInvulnerableEventOnItsBoss(): void
    {
        // Arrange
        $ravi          = $this->makeNpc(259445, "Rav'i", true, self::SENTINEL_HEALTH);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, 259445),
            $this->makeMechanic(NpcEncounterMechanicType::Invulnerable, 259445, [
                'spell_id'    => 1298221,
                'first_ms'    => 26_059,
                'cooldown_ms' => 54_416,
                'duration_ms' => 7_869,
            ]),
        ]), $this->makeNpc(900_000_009, 'Trash', false, 1_000), $ravi);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertStringEndsWith(
            PHP_EOL . 'raid_events+=/invulnerable,pull=05,target=BOSS_ravi_1,first=26.1,cooldown=54.4,duration=7.9,retarget=1',
            $result,
        );
    }

    #[Test]
    #[DataProvider('movementProvider')]
    public function toString_givenMovementRow_emitsAMovementEventForThePlayers(?int $cooldownMs, string $expectedLine): void
    {
        // Arrange
        $tazRah        = $this->makeNpc(238887, "Taz'Rah", true, self::SENTINEL_HEALTH);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, 238887),
            $this->makeMechanic(NpcEncounterMechanicType::Movement, null, [
                'spell_id'    => 1222100,
                'first_ms'    => 15_000,
                'cooldown_ms' => $cooldownMs,
                'duration_ms' => 4_000,
                'data'        => ['median_distance' => 16.2],
            ]),
        ]), $tazRah);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertStringEndsWith(PHP_EOL . $expectedLine, $result);
    }

    /**
     * SimulationCraft caps a movement at the distance walked within its cooldown, also when timestamps= schedules it,
     * so a single movement still needs a cooldown that covers the walk: 16.2 yards at 7 yards per second.
     *
     * @return array<string, array{0: int|null, 1: string}>
     */
    public static function movementProvider(): array
    {
        return [
            'recurring' => [35_000, 'raid_events+=/movement,pull=05,first=15.0,cooldown=35.0,distance=16.2'],
            'once'      => [null, 'raid_events+=/movement,pull=05,timestamps=15.0,distance=16.2,cooldown=2.4'],
        ];
    }

    #[Test]
    public function toString_givenFriendlyNpc_leavesItOutOfTheEnemies(): void
    {
        // Arrange
        $nalorakk      = $this->makeNpc(246404, 'Nalorakk', true, self::SENTINEL_HEALTH);
        $zulJarra      = $this->makeNpc(246409, "Zul'jarra", false, 1_000);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, 246404, ['encounter_id' => 3209]),
            $this->makeMechanic(NpcEncounterMechanicType::Friendly, 246409, ['encounter_id' => 3209]),
        ]), $nalorakk, $zulJarra);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertSame('raid_events+=/pull,pull=05,bloodlust=0,delay=000,enemies="BOSS_nalorakk_1":40750000', $result);
        Assert::assertTrue($raidEventPull->hasEnemies());
    }

    #[Test]
    public function hasEnemies_givenOnlyFriendlyNpcs_returnsFalse(): void
    {
        // Arrange
        $friend        = $this->makeNpc(246409, "Zul'jarra", false, 1_000);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, 246409, ['encounter_id' => 3209]),
            $this->makeMechanic(NpcEncounterMechanicType::Friendly, 246409, ['encounter_id' => 3209]),
        ]), $friend);

        // Act
        $result = $raidEventPull->hasEnemies();

        // Assert
        Assert::assertFalse($result);
    }

    #[Test]
    public function toString_givenAffixExemptEnemy_scalesItsHealthByTheKeyLevelOnly(): void
    {
        // Arrange - the other NPC has the very same base health but no row
        $sentinel      = $this->makeNpc(self::SENTINEL_NPC_ID, 'Sentinel of Winter', true, self::SENTINEL_HEALTH);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, self::SENTINEL_NPC_ID),
            $this->makeMechanic(NpcEncounterMechanicType::AffixExempt, self::SHIVERCORE_NPC_ID),
        ]), $sentinel, $this->makeShivercore(), $this->makeNpc(900_000_001, 'Ice Shard', false, self::SHIVERCORE_HEALTH));

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertSame(sprintf(
            'raid_events+=/pull,pull=05,bloodlust=0,delay=000,enemies=%s|"fractured-shivercore_1":%d|"ice-shard_1":%d',
            self::SENTINEL_ENTRY,
            self::SHIVERCORE_AFFIX_EXEMPT_HEALTH,
            self::SHIVERCORE_FORTIFIED_HEALTH,
        ), $result);
    }

    #[Test]
    public function toString_givenAffixExemptAdds_scalesTheirHealthByTheKeyLevelOnly(): void
    {
        // Arrange
        $sentinel      = $this->makeNpc(self::SENTINEL_NPC_ID, 'Sentinel of Winter', true, self::SENTINEL_HEALTH);
        $raidEventPull = $this->makeRaidEventPull(collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, self::SENTINEL_NPC_ID),
            $this->makeAddsMechanic($this->makeShivercore()),
            $this->makeMechanic(NpcEncounterMechanicType::AffixExempt, self::SHIVERCORE_NPC_ID),
        ]), $sentinel);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertStringContainsString(
            sprintf('name=Adds_05_fractured-shivercore,count=2,health=%d,', self::SHIVERCORE_AFFIX_EXEMPT_HEALTH),
            $result,
        );
    }

    #[Test]
    #[DataProvider('sharedHealthProvider')]
    public function toString_givenSharedHealthGroup_marksThePullOnlyWhenTheGroupHoldsEveryEnemy(bool $withTrash, bool $expectedSharedHealth): void
    {
        // Arrange
        $trinityNpcIds = [243028, 243029, 243030];
        $npcs          = [
            $this->makeNpc(243028, 'Meittik', true, self::SENTINEL_HEALTH),
            $this->makeNpc(243029, 'Kezkitt', true, self::SENTINEL_HEALTH),
            $this->makeNpc(243030, 'Lekshi', true, self::SENTINEL_HEALTH),
        ];
        if ($withTrash) {
            $npcs[] = $this->makeNpc(245410, 'Lasher', false, 1_000);
        }

        $mechanics = collect($trinityNpcIds)->map(
            fn(int $npcId): NpcEncounterMechanic => $this->makeMechanic(NpcEncounterMechanicType::Boss, $npcId, ['encounter_id' => 3199]),
        )->push($this->makeMechanic(NpcEncounterMechanicType::SharedHealth, 243028, [
            'encounter_id' => 3199,
            'count'        => 3,
            'data'         => ['npc_ids' => $trinityNpcIds],
        ]));
        $raidEventPull = $this->makeRaidEventPull($mechanics, ...$npcs);

        // Act
        $result = $raidEventPull->toString();

        // Assert
        Assert::assertSame(
            $expectedSharedHealth,
            str_starts_with($result, 'raid_events+=/pull,pull=05,bloodlust=0,delay=000,shared_health=1,enemies="BOSS_meittik_1":40750000|'),
        );
        Assert::assertSame($expectedSharedHealth, str_contains($result, 'shared_health'));
    }

    /**
     * @return array<string, array{0: bool, 1: bool}>
     */
    public static function sharedHealthProvider(): array
    {
        return [
            'only the group'    => [false, true],
            'trash in the pull' => [true, false],
        ];
    }

    #[Test]
    public function toString_givenABossWithoutMechanicsOfItsOwn_returnsThePullWithoutAnyMechanic(): void
    {
        // Arrange - every row below belongs to another encounter, whose boss is not in this pull
        $sentinel  = $this->makeNpc(self::SENTINEL_NPC_ID, 'Sentinel of Winter', true, self::SENTINEL_HEALTH);
        $shard     = $this->makeNpc(900_000_001, 'Ice Shard', false, self::SHIVERCORE_HEALTH);
        $other     = ['encounter_id' => 3209];
        $mechanics = collect([
            $this->makeMechanic(NpcEncounterMechanicType::Boss, self::SENTINEL_NPC_ID),
            $this->makeMechanic(NpcEncounterMechanicType::Boss, 246404, $other),
            $this->makeMechanic(NpcEncounterMechanicType::Friendly, 900_000_001, $other),
            $this->makeMechanic(NpcEncounterMechanicType::AffixExempt, self::SHIVERCORE_NPC_ID, $other),
            $this->makeMechanic(NpcEncounterMechanicType::SharedHealth, self::SENTINEL_NPC_ID, array_merge($other, [
                'data' => ['npc_ids' => [self::SENTINEL_NPC_ID, self::SHIVERCORE_NPC_ID, 900_000_001]],
            ])),
            $this->makeMechanic(NpcEncounterMechanicType::Invulnerable, self::SENTINEL_NPC_ID, array_merge($other, [
                'first_ms'    => 10_000,
                'cooldown_ms' => 30_000,
                'duration_ms' => 5_000,
            ])),
            $this->makeAddsMechanic($this->makeShivercore(), $other),
        ]);
        $enemyNpcs = [$sentinel, $this->makeShivercore(), $shard];

        // Act
        $result = $this->makeRaidEventPull($mechanics, ...$enemyNpcs)->toString();

        // Assert
        Assert::assertSame($this->makeRaidEventPull(null, ...$enemyNpcs)->toString(), $result);
        Assert::assertStringContainsString('"ice-shard_1"', $result);
    }

    /**
     * @param Collection<int, NpcEncounterMechanic>|null $mechanics
     */
    private function makeRaidEventPull(?Collection $mechanics, Npc ...$npcs): RaidEventPull
    {
        $killZone        = $this->getMockBuilder(KillZone::class)->onlyMethods(['getEnemies'])->getMock();
        $killZone->id    = 1;
        $killZone->index = self::PULL_INDEX;
        $killZone->method('getEnemies')->willReturn(new EloquentCollection(array_map(static function (Npc $npc): Enemy {
            $enemy         = new Enemy();
            $enemy->npc_id = $npc->id;
            $enemy->setRelation('npc', $npc);

            return $enemy;
        }, $npcs)));

        return new RaidEventPull($this->createMock(CoordinatesServiceInterface::class), $this->makeOptions(), $mechanics)
            ->calculateRaidEventPullEnemies($killZone, []);
    }

    private function makeOptions(): SimulationCraftRaidEventsOptions
    {
        $gameVersion     = new GameVersion();
        $gameVersion->id = self::GAME_VERSION_ID;
        $mappingVersion  = new MappingVersion();
        $mappingVersion->setRelation('gameVersion', $gameVersion);
        $dungeonRoute = new DungeonRoute();
        $dungeonRoute->setRelation('mappingVersion', $mappingVersion);

        $options = $this->createSimulationCraftRaidEventsOptions([
            'key_level'                   => 16,
            'affix'                       => implode(',', [SimulationCraftRaidEventsOptions::AFFIX_FORTIFIED, SimulationCraftRaidEventsOptions::AFFIX_TYRANNICAL]),
            'hp_percent'                  => 100,
            'raid_buffs_mask'             => 0,
            'simulate_bloodlust_per_pull' => '',
        ]);
        $options->setRelation('dungeonRoute', $dungeonRoute);

        return $options;
    }

    private function makeNpc(int $id, string $name, bool $boss, int $baseHealth): Npc
    {
        $npc                    = new Npc();
        $npc->id                = $id;
        $npc->name              = $name;
        $npc->classification_id = NpcClassification::ALL[$boss ? NpcClassification::NPC_CLASSIFICATION_BOSS : NpcClassification::NPC_CLASSIFICATION_NORMAL];

        $npcHealth                  = new NpcHealth();
        $npcHealth->npc_id          = $id;
        $npcHealth->game_version_id = self::GAME_VERSION_ID;
        $npcHealth->health          = $baseHealth;
        $npcHealth->percentage      = null;
        $npc->setRelation('npcHealths', new EloquentCollection([$npcHealth]));

        return $npc;
    }

    private function makeShivercore(): Npc
    {
        return $this->makeNpc(self::SHIVERCORE_NPC_ID, 'Fractured Shivercore', false, self::SHIVERCORE_HEALTH);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeMechanic(NpcEncounterMechanicType $type, ?int $npcId, array $attributes = []): NpcEncounterMechanic
    {
        return new NpcEncounterMechanic(array_merge([
            'dungeon_id'      => 1,
            'encounter_id'    => self::ENCOUNTER_ID,
            'type'            => $type,
            'npc_id'          => $npcId,
            'run_count'       => 4,
            'total_run_count' => 4,
        ], $attributes));
    }

    /**
     * Fractured Shivercore's row on Sentinel of Winter as built from real logs, unless overridden.
     *
     * @param array<string, mixed> $attributes
     */
    private function makeAddsMechanic(Npc $npc, array $attributes = []): NpcEncounterMechanic
    {
        $mechanic = $this->makeMechanic(NpcEncounterMechanicType::Adds, $npc->id, array_merge([
            'first_ms'    => 29_435,
            'cooldown_ms' => 63_177,
            'duration_ms' => 11_233,
            'count'       => 2,
            'data'        => ['attackable' => true, 'summoned_by_boss' => true],
        ], $attributes));
        $mechanic->setRelation('npc', $npc);

        return $mechanic;
    }

    /**
     * @return list<string>
     */
    private function getAddsNames(string $result): array
    {
        preg_match_all('/^raid_events\+=\/adds,pull=05,name=([^,]+),/m', $result, $matches);

        return $matches[1];
    }
}
