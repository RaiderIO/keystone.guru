<?php

namespace Tests\Feature\App\Models;

use App\Models\Expansion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Expansion')]
final class ExpansionTest extends PublicTestCase
{
    #[Test]
    public function Expansion_givenSeededRows_haveKeyMatchingShortname(): void
    {
        // Arrange
        $expectedKeysById = array_flip(Expansion::ALL);

        // Act
        $rows = Expansion::query()->orderBy('id')->get(['id', 'key', 'shortname']);

        // Assert
        $this->assertCount(count($expectedKeysById), $rows);
        foreach ($rows as $row) {
            $this->assertSame($expectedKeysById[$row->id], $row->key, sprintf('%s %d has the wrong key', Expansion::class, $row->id));
            $this->assertSame($row->shortname, $row->key, sprintf('%s %d has a key that differs from its shortname', Expansion::class, $row->id));
        }
    }

    #[Test]
    public function getIconUrl_givenKeyWithLegacyShortname_buildsPathFromKey(): void
    {
        // Arrange
        $expansion = new Expansion([
            'key'       => Expansion::EXPANSION_TWW,
            'shortname' => sprintf('legacy_%s', Expansion::EXPANSION_TWW),
        ]);

        // Act
        $iconUrl      = $expansion->getIconUrl();
        $wallpaperUrl = $expansion->getWallpaperUrl();

        // Assert
        $this->assertStringEndsWith(sprintf('expansions/%s.png', Expansion::EXPANSION_TWW), $iconUrl);
        $this->assertStringEndsWith(sprintf('dungeons/%s/wallpaper.jpg', Expansion::EXPANSION_TWW), $wallpaperUrl);
    }
}
