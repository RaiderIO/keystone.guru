<?php

namespace Tests\Feature\App\Logic\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * MDT 6.2.17 opens every dungeon file with an `MDT:RegisterDungeonLocation(...)` call to support automatic dungeon
 * selection in shared zones. Our Lua sandbox has no such method, so without a stub every mapping import died with
 * "attempt to call a nil value (method 'RegisterDungeonLocation')".
 */
#[Group('UsesLua')]
#[Group('MDT')]
final class MDTDungeonRegisterDungeonLocationTest extends PublicTestCase
{
    #[Test]
    public function getMDTDungeonID_givenDungeonFileRegistersItsLocation_returnsDungeonIndex(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('key', 'kingsrest')->firstOrFail();

        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);

        // Act
        $mdtDungeonId = $mdtDungeon->getMDTDungeonID();

        // Assert
        $this->assertSame($dungeon->mdt_id, $mdtDungeonId);
    }
}
