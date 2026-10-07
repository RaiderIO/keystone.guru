<?php

namespace Tests\Unit\App\Console\Commands\Mapping;

use App\Console\Commands\Mapping\Save;
use App\Models\Dungeon;
use App\Models\Expansion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Mapping')]
final class SaveTest extends PublicTestCase
{
    #[Test]
    public function getDungeonDataDirectoryPath_givenExpansionKeyWithLegacyShortname_buildsPathFromKey(): void
    {
        // Arrange
        $expansion = new Expansion([
            'key'       => Expansion::EXPANSION_TWW,
            'shortname' => sprintf('legacy_%s', Expansion::EXPANSION_TWW),
        ]);
        $dungeon = new Dungeon()->forceFill(['key' => 'arakara']);
        $dungeon->setRelation('expansion', $expansion);

        // Act
        $path = Save::getDungeonDataDirectoryPath('/seeders/dungeondata/', $dungeon);

        // Assert
        $this->assertSame(sprintf('/seeders/dungeondata/%s/arakara', Expansion::EXPANSION_TWW), $path);
    }
}
