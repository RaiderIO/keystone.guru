<?php

namespace Tests\Unit\App\Logic\SimulationCraft;

use App\Logic\SimulationCraft\RaidEventPull;
use App\Logic\SimulationCraft\RaidEventsCollection;
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
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSimulationCraftRaidEventsOptions;
use Tests\TestCase;

#[Group('SimulationCraft')]
final class RaidEventsCollectionTest extends TestCase
{
    use CreatesSimulationCraftRaidEventsOptions;

    private const int GAME_VERSION_ID = 1;
    private const int FRIENDLY_NPC_ID = 246409;
    private const int TRASH_NPC_ID    = 900_000_001;

    #[Test]
    public function toString_givenAPullOfOnlyFriendlyNpcs_skipsIt(): void
    {
        // Arrange - a friendly NPC only counts as one with the boss of its encounter in the pull; here it is both
        $mechanics = collect([
            new NpcEncounterMechanic(['encounter_id' => 3209, 'type' => NpcEncounterMechanicType::Boss, 'npc_id' => self::FRIENDLY_NPC_ID]),
            new NpcEncounterMechanic(['encounter_id' => 3209, 'type' => NpcEncounterMechanicType::Friendly, 'npc_id' => self::FRIENDLY_NPC_ID]),
        ]);
        $options = $this->makeOptions([
            $this->makeKillZone(1, $this->makeNpc(self::TRASH_NPC_ID, 'Trash')),
            $this->makeKillZone(2, $this->makeNpc(self::FRIENDLY_NPC_ID, "Zul'jarra")),
            $this->makeKillZone(3, $this->makeNpc(self::TRASH_NPC_ID, 'Trash')),
        ]);
        $killZonePathService = $this->createMock(KillZonePathServiceInterface::class);
        $killZonePathService->method('findPathsToKillZones')->willReturn([]);

        $raidEventsCollection = new RaidEventsCollection(
            $this->createMock(CoordinatesServiceInterface::class),
            $killZonePathService,
            $options,
            $mechanics,
        );

        // Act
        $result = $raidEventsCollection->calculateRaidEvents()->toString();

        // Assert
        Assert::assertStringContainsString('raid_events+=/pull,pull=01,', $result);
        Assert::assertStringContainsString('raid_events+=/pull,pull=02,', $result);
        Assert::assertStringNotContainsString('pull=03', $result);
        Assert::assertStringNotContainsString('zuljarra', $result);
    }

    #[Test]
    public function calculateRaidEvents_givenAnEmptyAndAFriendlyOnlyKillZoneInTheMiddle_numbersThePullsWithoutAGap(): void
    {
        // Arrange
        $mechanics = collect([
            new NpcEncounterMechanic(['encounter_id' => 3209, 'type' => NpcEncounterMechanicType::Boss, 'npc_id' => self::FRIENDLY_NPC_ID]),
            new NpcEncounterMechanic(['encounter_id' => 3209, 'type' => NpcEncounterMechanicType::Friendly, 'npc_id' => self::FRIENDLY_NPC_ID]),
        ]);
        $options = $this->makeOptions([
            $this->makeKillZone(1, $this->makeNpc(self::TRASH_NPC_ID, 'Trash')),
            $this->makeKillZone(2),
            $this->makeKillZone(3, $this->makeNpc(self::FRIENDLY_NPC_ID, "Zul'jarra")),
            $this->makeKillZone(4, $this->makeNpc(self::TRASH_NPC_ID, 'Trash')),
            $this->makeKillZone(5, $this->makeNpc(self::TRASH_NPC_ID, 'Trash')),
        ]);
        $killZonePathService = $this->createMock(KillZonePathServiceInterface::class);
        $killZonePathService->method('findPathsToKillZones')->willReturn([]);

        $raidEventsCollection = new RaidEventsCollection(
            $this->createMock(CoordinatesServiceInterface::class),
            $killZonePathService,
            $options,
            $mechanics,
        );

        // Act
        $result = $raidEventsCollection->calculateRaidEvents();

        // Assert
        preg_match_all('/^raid_events\+=\/pull,pull=(\d+),/m', $result->toString(), $matches);
        Assert::assertSame(['01', '02', '03'], $matches[1]);
        Assert::assertSame(
            [1, 4, 5],
            $result->getRaidEventPulls()->map(static fn(RaidEventPull $raidEventPull): int => $raidEventPull->getPullIndex())->all(),
        );
    }

    /**
     * @param list<KillZone> $killZones
     */
    private function makeOptions(array $killZones): SimulationCraftRaidEventsOptions
    {
        $gameVersion     = new GameVersion();
        $gameVersion->id = self::GAME_VERSION_ID;
        $mappingVersion  = new MappingVersion();
        $mappingVersion->setRelation('gameVersion', $gameVersion);
        $dungeonRoute        = new DungeonRoute();
        $dungeonRoute->title = 'Route';
        $dungeonRoute->setRelation('mappingVersion', $mappingVersion);
        $dungeonRoute->setRelation('killZones', new EloquentCollection($killZones));

        $options = $this->createSimulationCraftRaidEventsOptions([
            'key_level'                   => 16,
            'affix'                       => SimulationCraftRaidEventsOptions::AFFIX_FORTIFIED,
            'hp_percent'                  => 100,
            'raid_buffs_mask'             => 0,
            'simulate_bloodlust_per_pull' => '',
            'use_mounts'                  => false,
        ]);
        $options->setRelation('dungeonRoute', $dungeonRoute);

        return $options;
    }

    private function makeKillZone(int $index, ?Npc $npc = null): KillZone
    {
        $enemies = [];
        if ($npc !== null) {
            $enemy         = new Enemy();
            $enemy->npc_id = $npc->id;
            $enemy->setRelation('npc', $npc);
            $enemies[] = $enemy;
        }

        $killZone        = new KillZone();
        $killZone->id    = $index;
        $killZone->index = $index;
        $killZone->setRelation('enemies', new EloquentCollection($enemies));

        return $killZone;
    }

    private function makeNpc(int $id, string $name): Npc
    {
        $npc                    = new Npc();
        $npc->id                = $id;
        $npc->name              = $name;
        $npc->classification_id = NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL];

        $npcHealth                  = new NpcHealth();
        $npcHealth->game_version_id = self::GAME_VERSION_ID;
        $npcHealth->health          = 1_000_000;
        $npc->setRelation('npcHealths', new EloquentCollection([$npcHealth]));

        return $npc;
    }
}
