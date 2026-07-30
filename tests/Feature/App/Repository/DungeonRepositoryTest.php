<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
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

    private GameVersion $retailGameVersion;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository        = new DungeonRepository();
        $this->retailGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
    }

    #[Test]
    public function getAllMapIds_givenSeededDungeons_returnsUniqueCollection(): void
    {
        // Act
        $result = $this->repository->getAllMapIds();

        // Assert
        $this->assertNotEmpty($result);
        $this->assertEquals($result->count(), $result->unique()->count());
    }

    #[Test]
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
    public function getByChallengeModeIdOrFail_givenInvalidChallengeModeId_throwsModelNotFoundException(): void
    {
        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        $this->repository->getByChallengeModeIdOrFail(PHP_INT_MAX);
    }

    #[Test]
    public function getMappingVersionByVersion_givenExistingVersion_returnsMappingVersion(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->firstOrFail();
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()
            ->where('game_version_id', $this->retailGameVersion->id)
            ->firstOrFail();

        // Act
        $result = $this->repository->getMappingVersionByVersion($dungeon, $this->retailGameVersion, $mappingVersion->version);

        // Assert
        $this->assertInstanceOf(MappingVersion::class, $result);
        $this->assertEquals($mappingVersion->id, $result->id);
    }

    #[Test]
    public function getMappingVersionByVersion_givenNonExistentVersion_returnsNull(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->first();

        // Act
        $result = $this->repository->getMappingVersionByVersion($dungeon, $this->retailGameVersion, PHP_INT_MAX);

        // Assert
        $this->assertNull($result);
    }

    /**
     * #3720/#3754 follow-up: `version` is only unique per game_version_id, so a raw version number
     * shared with a mapping version on a DIFFERENT game version must not match here.
     */
    #[Test]
    public function getMappingVersionByVersion_givenVersionOnlyExistsOnAnotherGameVersion_returnsNull(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->firstOrFail();

        /** @var GameVersion $otherGameVersion */
        $otherGameVersion = GameVersion::query()
            ->where('id', '!=', $this->retailGameVersion->id)
            ->firstOrFail();

        // Inserted via insertGetId(), not MappingVersion::create(), so creating it doesn't trigger the
        // clone-on-create hook.
        $decoyId = MappingVersion::insertGetId([
            'game_version_id'                 => $otherGameVersion->id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => 999000,
            'enemy_forces_required'           => 0,
            'enemy_forces_required_teeming'   => 0,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 0,
            'facade_enabled'                  => false,
            'created_at'                      => now(),
            'updated_at'                      => now(),
        ]);

        try {
            // Act
            $result = $this->repository->getMappingVersionByVersion($dungeon, $this->retailGameVersion, 999000);

            // Assert
            $this->assertNull(
                $result,
                'A version number that only exists on a different game version must not match when looking up by retail.',
            );
        } finally {
            MappingVersion::query()->where('id', $decoyId)->delete();
        }
    }

    #[Test]
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
    public function getByInstanceId_givenNonExistentInstanceId_returnsNull(): void
    {
        // Act
        $result = $this->repository->getByInstanceId(PHP_INT_MAX);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getByMappingVersion_givenNullMappingVersion_returnsNull(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->first();

        // Act
        $result = $this->repository->getByMappingVersion($dungeon->challenge_mode_id, $this->retailGameVersion, null);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getByMappingVersion_givenValidIds_returnsDungeon(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->firstOrFail();
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()
            ->where('game_version_id', $this->retailGameVersion->id)
            ->firstOrFail();

        // Act
        $result = $this->repository->getByMappingVersion($dungeon->challenge_mode_id, $this->retailGameVersion, $mappingVersion->version);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($dungeon->challenge_mode_id, $result->challenge_mode_id);
    }

    /**
     * #3720/#3754 follow-up: the version/game_version_id pair must match the SAME mapping version row.
     * A dungeon that has a retail row with some version A and an unrelated decoy row (different game
     * version) with version B must not match when queried for (retail, B) - two independent
     * `whereRelation()` EXISTS clauses would each be satisfiable by a different row and wrongly match.
     */
    #[Test]
    public function getByMappingVersion_givenVersionOnlyExistsOnAnotherGameVersion_returnsNull(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->firstOrFail();
        // The dungeon must already have SOME retail mapping version for the "two independent EXISTS
        // clauses" bug to be exercised at all.
        $dungeon->mappingVersions()->where('game_version_id', $this->retailGameVersion->id)->firstOrFail();

        /** @var GameVersion $otherGameVersion */
        $otherGameVersion = GameVersion::query()
            ->where('id', '!=', $this->retailGameVersion->id)
            ->firstOrFail();

        $decoyId = MappingVersion::insertGetId([
            'game_version_id'                 => $otherGameVersion->id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => 999000,
            'enemy_forces_required'           => 0,
            'enemy_forces_required_teeming'   => 0,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 0,
            'facade_enabled'                  => false,
            'created_at'                      => now(),
            'updated_at'                      => now(),
        ]);

        try {
            // Act
            $result = $this->repository->getByMappingVersion($dungeon->challenge_mode_id, $this->retailGameVersion, 999000);

            // Assert
            $this->assertNull(
                $result,
                'The dungeon has no RETAIL mapping version with this version number - it must not match via its unrelated decoy on another game version.',
            );
        } finally {
            MappingVersion::query()->where('id', $decoyId)->delete();
        }
    }
}
