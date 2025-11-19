<?php

namespace Tests\Feature\Mapping;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DefaultFloorTest extends TestCase
{
    #[Test]
    #[Group('Mapping')]
    public function mapTilesExistence_givenDungeon_shouldHaveAllMapTilesAvailable(): void
    {
        // Arrange
        $zoomLevels = 5;
        /** @var Collection<Dungeon> $dungeons */
        $dungeons = Dungeon::all();

        // Act & Assert
        foreach ($dungeons as $dungeon) {
            if (in_array($dungeon->key, ['stormwindcityhorrificvision'
            ])) {
                continue;
            }

            // Act
            $defaultFloors = $dungeon->floors->filter(fn(Floor $floor) => $floor->default);

            // Assert
            Assert::assertNotEmpty($defaultFloors, sprintf('No default floor found for dungeon %s', $dungeon->key));
            Assert::assertCount(1, $defaultFloors, sprintf('Multiple default floors found for dungeon %s', $dungeon->key));
        }
    }
}
