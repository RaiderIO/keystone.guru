<?php

namespace Tests\Feature\View\Common\Maps;

use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * Right-clicking an enemy opens its NPC Compendium page only when the map hands the JS a compendium URL.
 */
#[Group('View')]
#[Group('Compendium')]
final class MapNpcCompendiumOptionTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string OPTION_NULL = '"npcCompendiumBaseUrl":null';
    private const string OPTION_URL  = '"npcCompendiumBaseUrl":"';

    #[Test]
    public function explore_givenUserOnRetail_passesTheNpcCompendiumUrl(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $user        = User::factory()->create(['game_version_id' => $gameVersion->id]);

        try {
            // Act
            $response = $this->actingAs($user)->get($this->exploreUrl($gameVersion));

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_URL, false);
            $response->assertDontSee(self::OPTION_NULL, false);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function explore_givenUserOnClassicEra_passesNoNpcCompendiumUrl(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $user        = User::factory()->create(['game_version_id' => $gameVersion->id]);

        try {
            // Act
            $response = $this->actingAs($user)->get($this->exploreUrl($gameVersion));

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_NULL, false);
        } finally {
            $user->delete();
        }
    }

    private function exploreUrl(GameVersion $gameVersion): string
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            facadeEnabled:       false,
            dungeonActive:       true,
            requireDefaultFloor: true,
            gameVersion:         $gameVersion,
        );
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        return route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]);
    }
}
