<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\Faction;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
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
    private const string SENTINEL_MAPPING_HASH = 'sentinel-hash-3995';

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
    public function exceptionMessage_givenTheDungeonTheNpcsBelongTo_namesIt(): void
    {
        // Arrange & Act - the message is composed in the exception, and the owning dungeon is only known
        // in the real failure direction (MDT hands us dungeon B's NPCs while importing dungeon A), which
        // cannot be reproduced through MDT without a .lua holding the wrong dungeon's data
        $withOwner    = new MDTMappingNpcSetReplacedException('the_blinding_vale', 32, 26, 0, 'den_of_nalorakk');
        $withoutOwner = new MDTMappingNpcSetReplacedException('the_blinding_vale', 32, 26, 0, null);

        // Assert - the diagnosis is the whole point: an abort that does not say whose data this is just
        // sends the next person hunting through .lua files
        $this->assertStringContainsString('den_of_nalorakk', $withOwner->getMessage());
        $this->assertStringContainsString('the_blinding_vale', $withOwner->getMessage());
        $this->assertStringContainsString('--force', $withOwner->getMessage());

        $this->assertStringNotContainsString('den_of_nalorakk', $withoutOwner->getMessage());
        $this->assertStringContainsString('--force', $withoutOwner->getMessage());
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
    public function assertMDTNpcSetIsPlausible_givenForceImport_doesNotThrow(): void
    {
        // Arrange
        $dungeon               = $this->getDungeon(DungeonKey::MURDER_ROW);
        $foreignMappingVersion = $this->getMappingVersionWithEnemies(DungeonKey::DEN_OF_NALORAKK);

        $mappingVersionCountBefore = $dungeon->mappingVersions()->count();

        // Act
        $this->assertMDTNpcSetIsPlausible($dungeon, $foreignMappingVersion, true);

        // Assert - --force must stay able to push a genuine wholesale remap through, and the guard
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

    #[Test]
    public function assertMDTNpcSetIsPlausible_givenOverlapBelowTheMinimum_throws(): void
    {
        // Arrange - 2 of the dungeon's own NPCs and 8 foreign ones: 20% survives, under the 50% minimum.
        // The threshold, not just a total miss, is what refuses an import.
        $dungeon                 = $this->getDungeon(DungeonKey::MURDER_ROW);
        $temporaryMappingVersion = $this->createTemporaryMappingVersionWithNpcs($dungeon, 2, 8);

        try {
            // Assert
            $this->expectException(MDTMappingNpcSetReplacedException::class);

            // Act
            $this->assertMDTNpcSetIsPlausible($dungeon, $temporaryMappingVersion, false);
        } finally {
            $temporaryMappingVersion->delete();
        }
    }

    #[Test]
    public function assertMDTNpcSetIsPlausible_givenOverlapAtTheMinimum_doesNotThrow(): void
    {
        // Arrange - 6 of the dungeon's own NPCs and 4 foreign ones: 60% survives, which is roughly the
        // lowest overlap a real MDT transition has ever produced (59%) and must keep working
        $dungeon                 = $this->getDungeon(DungeonKey::MURDER_ROW);
        $temporaryMappingVersion = $this->createTemporaryMappingVersionWithNpcs($dungeon, 6, 4);

        try {
            // Act
            $this->assertMDTNpcSetIsPlausible($dungeon, $temporaryMappingVersion, false);

            // Assert
            $this->assertSame(
                10,
                $temporaryMappingVersion->enemies()->distinct()->count('npc_id'),
                'The fixture should hold exactly the 10 NPCs the overlap was calculated from.',
            );
        } finally {
            $temporaryMappingVersion->delete();
        }
    }

    #[Test]
    public function importMappingVersionFromMDT_givenMDTNpcsDisjointFromCurrentMapping_createsNothingAndLeavesTheHashUntouched(): void
    {
        // Arrange - a current mapping version holding another dungeon's NPCs, which is what MDT 6.2.1
        // effectively produced. Driven through the public entry point on purpose: the guard's whole safety
        // claim is about WHERE it runs - before a mapping version is created and before the hash is
        // stamped - and a test that reflects into the private method cannot see that.
        $dungeon               = $this->getDungeon(DungeonKey::MURDER_ROW);
        $currentMappingVersion = $this->getMappingVersionWithEnemies(DungeonKey::MURDER_ROW);
        $foreignNpcIds         = $this->getMappingVersionWithEnemies(DungeonKey::DEN_OF_NALORAKK)
            ->enemies()
            ->whereNotNull('npc_id')
            ->distinct()
            ->limit(3)
            ->pluck('npc_id')
            ->all();

        $temporaryMappingVersion = MappingVersion::create([
            'game_version_id'                 => $currentMappingVersion->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $currentMappingVersion->version + 1000,
            'enemy_forces_required'           => $currentMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $currentMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $currentMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $currentMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $currentMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
            'mdt_mapping_hash'                => self::SENTINEL_MAPPING_HASH,
        ]);

        try {
            // MappingVersion::boot() cloned the real enemies in - replace them with a foreign dungeon's so
            // the incoming MDT set is disjoint. Query builder delete: an Eloquent one would cascade.
            Enemy::where('mapping_version_id', $temporaryMappingVersion->id)->delete();

            foreach ($foreignNpcIds as $foreignNpcId) {
                Enemy::create([
                    'mapping_version_id' => $temporaryMappingVersion->id,
                    'floor_id'           => $dungeon->floors()->firstOrFail()->id,
                    'npc_id'             => $foreignNpcId,
                    'faction'            => Faction::ALL[Faction::FACTION_UNSPECIFIED],
                    'lat'                => -100.0,
                    'lng'                => 100.0,
                ]);
            }

            $mappingVersionCountBefore = $dungeon->mappingVersions()->count();

            // Act
            $exception = null;

            try {
                app(MDTMappingImportServiceInterface::class)->importMappingVersionFromMDT(
                    app(MappingServiceInterface::class),
                    $dungeon,
                    $temporaryMappingVersion->gameVersion,
                );
            } catch (MDTMappingNpcSetReplacedException $caught) {
                $exception = $caught;
            }

            // Assert
            $this->assertNotNull($exception, 'The import should have been refused.');

            $this->assertSame(
                $mappingVersionCountBefore,
                $dungeon->mappingVersions()->count(),
                'The guard must run before a mapping version is created - otherwise every refused import ' .
                'leaves a created-then-deleted mapping version behind.',
            );

            $this->assertSame(
                self::SENTINEL_MAPPING_HASH,
                $temporaryMappingVersion->refresh()->mdt_mapping_hash,
                'mdt_mapping_hash must be untouched, or the next run sees "no change detected" and never retries.',
            );
        } finally {
            // Eloquent delete so MappingVersion::boot()'s cascade takes the enemies with it
            $temporaryMappingVersion->delete();
        }
    }

    /**
     * A throwaway mapping version for $dungeon holding $ownNpcCount of the NPCs MDT actually offers for it
     * plus $foreignNpcCount from another dungeon, so the guard sees a precise, controlled overlap.
     */
    private function createTemporaryMappingVersionWithNpcs(
        Dungeon $dungeon,
        int     $ownNpcCount,
        int     $foreignNpcCount,
    ): MappingVersion {
        $currentMappingVersion = $this->getMappingVersionWithEnemies(DungeonKey::MURDER_ROW);

        $ownNpcIds = $currentMappingVersion->enemies()
            ->whereNotNull('npc_id')
            ->distinct()
            ->limit($ownNpcCount)
            ->pluck('npc_id')
            ->all();

        $foreignNpcIds = $this->getMappingVersionWithEnemies(DungeonKey::DEN_OF_NALORAKK)
            ->enemies()
            ->whereNotNull('npc_id')
            ->distinct()
            ->limit($foreignNpcCount)
            ->pluck('npc_id')
            ->all();

        $temporaryMappingVersion = MappingVersion::create([
            'game_version_id'                 => $currentMappingVersion->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $currentMappingVersion->version + 1000,
            'enemy_forces_required'           => $currentMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $currentMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $currentMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $currentMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $currentMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
            'mdt_mapping_hash'                => self::SENTINEL_MAPPING_HASH,
        ]);

        // MappingVersion::boot() cloned the real enemies in - replace them with the controlled mix. Query
        // builder delete: an Eloquent one would cascade.
        Enemy::where('mapping_version_id', $temporaryMappingVersion->id)->delete();

        foreach (array_merge($ownNpcIds, $foreignNpcIds) as $npcId) {
            Enemy::create([
                'mapping_version_id' => $temporaryMappingVersion->id,
                'floor_id'           => $dungeon->floors()->firstOrFail()->id,
                'npc_id'             => $npcId,
                'faction'            => Faction::ALL[Faction::FACTION_UNSPECIFIED],
                'lat'                => -100.0,
                'lng'                => 100.0,
            ]);
        }

        return $temporaryMappingVersion;
    }

    private function assertMDTNpcSetIsPlausible(
        Dungeon         $dungeon,
        ?MappingVersion $currentMappingVersion,
        bool            $forceImport,
    ): void {
        $service = app(MDTMappingImportServiceInterface::class);
        $this->assertInstanceOf(MDTMappingImportService::class, $service);

        new ReflectionMethod($service, 'assertMDTNpcSetIsPlausible')
            ->invoke($service, $dungeon, $currentMappingVersion, $forceImport);
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
