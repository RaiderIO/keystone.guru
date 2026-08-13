<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Mapping\MappingVersion;
use App\Service\MDT\Exceptions\MDTMappingNpcSetReplacedException;
use App\Service\MDT\MDTMappingImportService;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

/**
 * MDT 6.2.1 shipped a `TheBlindingVale.lua` that was a duplicate of Den of Nalorakk's, and the import
 * replaced The Blinding Vale's entire mapping with Den of Nalorakk's enemies at exit code 0 without a single
 * warning (#3995). These tests pin the guard that now refuses that.
 *
 * The guard is driven directly rather than through a full import: it reads nothing but `$dungeon` and
 * `$currentMappingVersion->enemies()`, so handing it one dungeon's MDT data and another dungeon's mapping
 * version reproduces the exact "MDT gave us the wrong dungeon's file" condition without writing a single row.
 */
#[Group('MDT')]
#[Group('MappingVersion')]
final class MDTMappingImportNpcSetGuardTest extends PublicTestCase
{
    #[Test]
    public function assertMDTNpcSetIsPlausible_givenMDTNpcsDisjointFromCurrentMapping_throws(): void
    {
        // Arrange
        $dungeon               = $this->getDungeon(DungeonKey::MURDER_ROW);
        $foreignMappingVersion = $this->getMappingVersionWithEnemies(DungeonKey::DEN_OF_NALORAKK);

        // Assert
        $this->expectException(MDTMappingNpcSetReplacedException::class);

        // Act
        $this->assertMDTNpcSetIsPlausible($dungeon, $foreignMappingVersion, false);
    }

    #[Test]
    public function assertMDTNpcSetIsPlausible_givenMDTNpcsDisjointFromCurrentMapping_namesTheDungeonTheNpcsBelongTo(): void
    {
        // Arrange
        $dungeon               = $this->getDungeon(DungeonKey::MURDER_ROW);
        $foreignMappingVersion = $this->getMappingVersionWithEnemies(DungeonKey::DEN_OF_NALORAKK);

        // Act
        $message = null;

        try {
            $this->assertMDTNpcSetIsPlausible($dungeon, $foreignMappingVersion, false);
        } catch (MDTMappingNpcSetReplacedException $exception) {
            $message = $exception->getMessage();
        }

        // Assert - the diagnosis is the whole point: an abort that does not say whose data this is just
        // sends the next person hunting through .lua files
        $this->assertNotNull($message, 'The guard should have thrown.');
        $this->assertStringContainsString(DungeonKey::MURDER_ROW->value, $message);
        $this->assertStringContainsString('--allow-npc-set-replacement', $message);
    }

    #[Test]
    public function findDungeonKeyOwningNpcs_givenAnotherDungeonsNpcs_returnsThatDungeonsKey(): void
    {
        // Arrange - the #3995 signature: the NPCs MDT handed us for one dungeon are another dungeon's.
        // Driven directly because reproducing it through MDT needs a .lua file that holds the wrong
        // dungeon's data, which is exactly the upstream bug we do not want a test pinned to.
        $dungeon       = $this->getDungeon(DungeonKey::MURDER_ROW);
        $foreignNpcIds = $this->getMappingVersionWithEnemies(DungeonKey::DEN_OF_NALORAKK)
            ->enemies()
            ->whereNotNull('npc_id')
            ->distinct()
            ->pluck('npc_id')
            ->all();

        // Act
        $service = app(MDTMappingImportServiceInterface::class);
        $owner   = new ReflectionMethod($service, 'findDungeonKeyOwningNpcs')
            ->invoke($service, $dungeon, $foreignNpcIds);

        // Assert
        $this->assertSame(DungeonKey::DEN_OF_NALORAKK->value, $owner);
    }

    #[Test]
    public function findDungeonKeyOwningNpcs_givenTheDungeonsOwnNpcs_returnsNull(): void
    {
        // Arrange
        $dungeon   = $this->getDungeon(DungeonKey::MURDER_ROW);
        $ownNpcIds = $this->getMappingVersionWithEnemies(DungeonKey::MURDER_ROW)
            ->enemies()
            ->whereNotNull('npc_id')
            ->distinct()
            ->pluck('npc_id')
            ->all();

        // Act
        $service = app(MDTMappingImportServiceInterface::class);
        $owner   = new ReflectionMethod($service, 'findDungeonKeyOwningNpcs')
            ->invoke($service, $dungeon, $ownNpcIds);

        // Assert - no other dungeon owns them, so there is nothing to accuse
        $this->assertNull($owner);
    }

    #[Test]
    public function assertMDTNpcSetIsPlausible_givenAllowNpcSetReplacement_doesNotThrow(): void
    {
        // Arrange
        $dungeon               = $this->getDungeon(DungeonKey::MURDER_ROW);
        $foreignMappingVersion = $this->getMappingVersionWithEnemies(DungeonKey::DEN_OF_NALORAKK);

        $mappingVersionCountBefore = $dungeon->mappingVersions()->count();

        // Act
        $this->assertMDTNpcSetIsPlausible($dungeon, $foreignMappingVersion, true);

        // Assert - a genuine wholesale remap must stay possible without editing code, and the guard
        // itself must never write anything either way
        $this->assertSame($mappingVersionCountBefore, $dungeon->mappingVersions()->count());
    }

    #[Test]
    public function assertMDTNpcSetIsPlausible_givenMDTNpcsMatchingCurrentMapping_doesNotThrow(): void
    {
        // Arrange
        $dungeon = $this->getDungeon(DungeonKey::MURDER_ROW);

        $mappingVersionCountBefore = $dungeon->mappingVersions()->count();

        // Act
        $this->assertMDTNpcSetIsPlausible($dungeon, $this->getMappingVersionWithEnemies(DungeonKey::MURDER_ROW), false);

        // Assert
        $this->assertSame($mappingVersionCountBefore, $dungeon->mappingVersions()->count());
    }

    #[Test]
    public function assertMDTNpcSetIsPlausible_givenNoCurrentMappingVersion_doesNotThrow(): void
    {
        // Arrange - a first-ever import has nothing to compare against
        $dungeon = $this->getDungeon(DungeonKey::MURDER_ROW);

        $mappingVersionCountBefore = $dungeon->mappingVersions()->count();

        // Act
        $this->assertMDTNpcSetIsPlausible($dungeon, null, false);

        // Assert
        $this->assertSame($mappingVersionCountBefore, $dungeon->mappingVersions()->count());
    }

    private function assertMDTNpcSetIsPlausible(
        Dungeon         $dungeon,
        ?MappingVersion $currentMappingVersion,
        bool            $allowNpcSetReplacement,
    ): void {
        $service = app(MDTMappingImportServiceInterface::class);
        $this->assertInstanceOf(MDTMappingImportService::class, $service);

        new ReflectionMethod($service, 'assertMDTNpcSetIsPlausible')
            ->invoke($service, $dungeon, $currentMappingVersion, $allowNpcSetReplacement);
    }

    private function getDungeon(DungeonKey $dungeonKey): Dungeon
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::where('key', $dungeonKey->value)->first();

        if ($dungeon === null) {
            $this->fail(sprintf('Dungeon %s not found.', $dungeonKey->value));
        }

        return $dungeon;
    }

    private function getMappingVersionWithEnemies(DungeonKey $dungeonKey): MappingVersion
    {
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = $this->getDungeon($dungeonKey)
            ->mappingVersions()
            ->orderByDesc('id')
            ->get()
            ->first(static fn(MappingVersion $mappingVersion): bool => $mappingVersion->enemies()->exists());

        if ($mappingVersion === null) {
            $this->fail(sprintf('Dungeon %s has no mapping version with enemies.', $dungeonKey->value));
        }

        return $mappingVersion;
    }
}
