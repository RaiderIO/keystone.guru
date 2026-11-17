<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\Mapping\MappingVersionRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersionRepository')]
final class MappingVersionRepositoryTest extends PublicTestCase
{
    use CreatesDungeon;

    private MappingVersionRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MappingVersionRepository();
    }

    #[Test]
    public function getNewestPerGameVersion_givenSeveralVersionsPerGameVersion_returnsTheNewestOfEach(): void
    {
        // Arrange - the default game version has versions 1 (from createDungeon) and 3, the other game version 2
        $dungeon            = $this->createDungeon();
        $defaultGameVersion = GameVersion::getDefaultGameVersion();
        $otherGameVersion   = GameVersion::query()->whereKeyNot($defaultGameVersion->id)->firstOrFail();
        $otherVersion       = $this->createMappingVersion($dungeon, $otherGameVersion, 2);
        $newestVersion      = $this->createMappingVersion($dungeon, $defaultGameVersion, 3);

        // Act
        $result = $this->repository->getNewestPerGameVersion($dungeon->id);

        // Assert
        $this->assertEqualsCanonicalizing([$newestVersion->id, $otherVersion->id], $result->pluck('id')->all());
    }

    #[Test]
    public function getNewestPerGameVersion_givenAnotherDungeonWithNewerVersions_returnsOnlyTheGivenDungeons(): void
    {
        // Arrange
        $dungeon      = $this->createDungeon();
        $otherDungeon = $this->createDungeon();
        $this->createMappingVersion($otherDungeon, GameVersion::getDefaultGameVersion(), 5);

        // Act
        $result = $this->repository->getNewestPerGameVersion($dungeon->id);

        // Assert
        $this->assertSame([$dungeon->mappingVersions()->firstOrFail()->id], $result->pluck('id')->all());
    }

    #[Test]
    public function getNewestPerGameVersion_givenDungeonWithoutMappingVersions_returnsEmptyCollection(): void
    {
        // Arrange
        $dungeon = $this->createDungeon(withMappingVersion: false);

        // Act
        $result = $this->repository->getNewestPerGameVersion($dungeon->id);

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    private function createMappingVersion(Dungeon $dungeon, GameVersion $gameVersion, int $version): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => $gameVersion->id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $version,
            'enemy_forces_required'           => 100,
            'enemy_forces_required_teeming'   => null,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 1800,
            'facade_enabled'                  => false,
            'mdt_mapping_hash'                => null,
            'mdt_changes_pending'             => false,
        ]);
    }
}
