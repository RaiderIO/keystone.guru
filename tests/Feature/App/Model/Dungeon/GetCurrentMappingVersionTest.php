<?php

namespace Tests\Feature\App\Model\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Dungeon')]
#[Group('GetCurrentMappingVersion')]
final class GetCurrentMappingVersionTest extends PublicTestCase
{
    use CreatesDungeon;

    #[Test]
    public function getCurrentMappingVersion_givenUserWhileAnotherUserIsLoggedIn_returnsThePassedUsersMappingVersion(): void
    {
        // Arrange
        $classicEra   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $retail       = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $user         = null;
        $loggedInUser = null;
        unset($_COOKIE['game_version']);

        try {
            $dungeon               = $this->createDungeon();
            $classicMappingVersion = $this->createMappingVersion($dungeon, $classicEra);
            $user                  = User::factory()->create(['game_version_id' => $classicEra->id]);
            $loggedInUser          = User::factory()->create(['game_version_id' => $retail->id]);
            $this->actingAs($loggedInUser);

            // Act
            $result = $dungeon->getCurrentMappingVersion(null, $user);

            // Assert
            $this->assertSame($classicMappingVersion->id, $result?->id);
        } finally {
            $user?->delete();
            $loggedInUser?->delete();
        }
    }

    #[Test]
    public function getCurrentMappingVersion_givenNullWhileUserIsLoggedIn_returnsTheDefaultGameVersionMappingVersion(): void
    {
        // Arrange
        $classicEra   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $loggedInUser = null;
        unset($_COOKIE['game_version']);

        try {
            $dungeon               = $this->createDungeon();
            $defaultMappingVersion = $dungeon->mappingVersions()->sole();
            $this->createMappingVersion($dungeon, $classicEra);
            $loggedInUser = User::factory()->create(['game_version_id' => $classicEra->id]);
            $this->actingAs($loggedInUser);

            // Act
            $result = $dungeon->getCurrentMappingVersion(null, null);

            // Assert
            $this->assertSame($defaultMappingVersion->id, $result?->id);
        } finally {
            $loggedInUser?->delete();
        }
    }

    #[Test]
    public function getCurrentMappingVersion_givenUserOnGameVersionWithoutMapping_returnsTheDefaultGameVersionMappingVersion(): void
    {
        // Arrange
        $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $mop        = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_MOP);
        $user       = null;
        unset($_COOKIE['game_version']);

        try {
            $dungeon               = $this->createDungeon();
            $defaultMappingVersion = $dungeon->mappingVersions()->sole();
            // Newer than the default game version's, so falling through to the most recent one would return it instead
            $this->createMappingVersion($dungeon, $mop);
            $user = User::factory()->create(['game_version_id' => $classicEra->id]);

            // Act
            $result = $dungeon->getCurrentMappingVersion(null, $user);

            // Assert
            $this->assertSame($defaultMappingVersion->id, $result?->id);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function getCurrentMappingVersion_givenGameVersionWithoutMapping_returnsThePassedUsersMappingVersion(): void
    {
        // Arrange
        $classicEra   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $mop          = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_MOP);
        $retail       = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $user         = null;
        $loggedInUser = null;
        unset($_COOKIE['game_version']);

        try {
            $dungeon               = $this->createDungeon();
            $classicMappingVersion = $this->createMappingVersion($dungeon, $classicEra);
            $user                  = User::factory()->create(['game_version_id' => $classicEra->id]);
            $loggedInUser          = User::factory()->create(['game_version_id' => $retail->id]);
            $this->actingAs($loggedInUser);

            // Act
            $result = $dungeon->getCurrentMappingVersion($mop, $user);

            // Assert
            $this->assertSame($classicMappingVersion->id, $result?->id);
        } finally {
            $user?->delete();
            $loggedInUser?->delete();
        }
    }

    private function createMappingVersion(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion
    {
        $mappingVersion = MappingVersion::create([
            'game_version_id'                 => $gameVersion->id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => 2,
            'enemy_forces_required'           => 100,
            'enemy_forces_required_teeming'   => null,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 1800,
            'facade_enabled'                  => false,
            'mdt_mapping_hash'                => null,
            'mdt_changes_pending'             => false,
        ]);

        $dungeon->unsetRelation('mappingVersions');

        return $mappingVersion;
    }
}
