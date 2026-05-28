<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\DungeonRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRepository')]
final class DungeonRepositoryTest extends PublicTestCase
{
    private DungeonRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DungeonRepository();
    }

    #[Test]
    #[Group('DungeonRepository')]
    public function getAllMapIds_givenSeededDungeons_returnsUniqueCollection(): void
    {
        // Act
        $result = $this->repository->getAllMapIds();

        // Assert
        $this->assertNotEmpty($result);
        $this->assertEquals($result->count(), $result->unique()->count());
    }

    #[Test]
    #[Group('DungeonRepository')]
    public function getByChallengeModeIdOrFail_givenValidChallengeModeId_returnsDungeon(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->first();

        // Act
        $result = $this->repository->getByChallengeModeIdOrFail($dungeon->challenge_mode_id);

        // Assert
        $this->assertInstanceOf(Dungeon::class, $result);
        $this->assertEquals($dungeon->challenge_mode_id, $result->challenge_mode_id);
    }

    #[Test]
    #[Group('DungeonRepository')]
    public function getByChallengeModeIdOrFail_givenInvalidChallengeModeId_throwsModelNotFoundException(): void
    {
        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->repository->getByChallengeModeIdOrFail(PHP_INT_MAX);
    }

    #[Test]
    #[Group('DungeonRepository')]
    public function getMappingVersionByVersion_givenExistingVersion_returnsMappingVersion(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->first();
        $mappingVersion = $dungeon->mappingVersions()->first();

        // Act
        $result = $this->repository->getMappingVersionByVersion($dungeon, $mappingVersion->version);

        // Assert
        $this->assertInstanceOf(MappingVersion::class, $result);
        $this->assertEquals($mappingVersion->id, $result->id);
    }

    #[Test]
    #[Group('DungeonRepository')]
    public function getMappingVersionByVersion_givenNonExistentVersion_returnsNull(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->first();

        // Act
        $result = $this->repository->getMappingVersionByVersion($dungeon, PHP_INT_MAX);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('DungeonRepository')]
    public function getByInstanceId_givenValidInstanceId_returnsDungeon(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('instance_id')->first();

        // Act
        $result = $this->repository->getByInstanceId($dungeon->instance_id);

        // Assert
        $this->assertInstanceOf(Dungeon::class, $result);
        $this->assertEquals($dungeon->instance_id, $result->instance_id);
    }

    #[Test]
    #[Group('DungeonRepository')]
    public function getByInstanceId_givenNonExistentInstanceId_returnsNull(): void
    {
        // Act
        $result = $this->repository->getByInstanceId(PHP_INT_MAX);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('DungeonRepository')]
    public function getByMappingVersion_givenNullMappingVersion_returnsNull(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->first();

        // Act
        $result = $this->repository->getByMappingVersion($dungeon->challenge_mode_id, null);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[Group('DungeonRepository')]
    public function getByMappingVersion_givenValidIds_returnsDungeon(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->first();
        $mappingVersion = $dungeon->mappingVersions()->first();

        // Act
        $result = $this->repository->getByMappingVersion($dungeon->challenge_mode_id, $mappingVersion->version);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($dungeon->challenge_mode_id, $result->challenge_mode_id);
    }
}
