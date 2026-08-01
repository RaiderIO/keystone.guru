<?php

namespace Tests\Feature\App\Models;

use App\Models\Faction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Faction')]
final class FactionTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('factionKeyProvider')]
    public function iconUrl_givenSeededFaction_returnsAssetsImageUrl(string $key): void
    {
        // Arrange
        $faction = Faction::query()->where('key', $key)->firstOrFail();

        // Act
        $iconUrl = $faction->icon_url;

        // Assert
        $this->assertSame(ksgAssetImage(sprintf('factions/%s.png', $key)), $iconUrl);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function factionKeyProvider(): array
    {
        return [
            Faction::FACTION_UNSPECIFIED => [Faction::FACTION_UNSPECIFIED],
            Faction::FACTION_HORDE       => [Faction::FACTION_HORDE],
            Faction::FACTION_ALLIANCE    => [Faction::FACTION_ALLIANCE],
        ];
    }

    #[Test]
    public function toArray_givenSeededFaction_containsIconUrlAndOmitsIconFile(): void
    {
        // Arrange
        $faction = Faction::query()->firstOrFail();

        // Act
        $array = $faction->toArray();

        // Assert - the map controls read faction.icon_url off this payload; the `iconfile` File
        // relation and the vestigial icon_file_id column must never reach the front-end again.
        $this->assertArrayHasKey('icon_url', $array);
        $this->assertArrayNotHasKey('iconfile', $array);
        $this->assertArrayNotHasKey('icon_file_id', $array);
    }
}
