<?php

namespace Tests\Feature\App\Logic\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\FacadeNotConfiguredException;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * getClonesAsEnemies() used to silently degrade when a mapping version had facade_enabled=true but no
 * usable facade was actually found (no facade floor, or a facade floor with no floor unions to
 * redistribute through) - stranding enemies on the facade floor without any signal that something was
 * misconfigured (#3742). Per review feedback on #3739 it now throws instead, so the mapping version's
 * facade setup gets fixed rather than worked around.
 */
#[Group('MDT')]
final class MDTDungeonGetClonesAsEnemiesFacadeTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function getClonesAsEnemies_givenFacadeEnabledButNoFacadeFloorAmongGivenFloors_throwsFacadeNotConfiguredException(): void
    {
        // Arrange - a real facade_enabled mapping version, MDT-supported, with a non-facade floor to
        // additionally hand in. findDungeon() (not the discard-the-mapping-version presets) so the
        // mapping version used below is the exact one every requirement was validated against.
        [$dungeon, $mappingVersion] = $this->findDungeon(
            facadeEnabled: true,
            resolve:       static function (Dungeon $dungeon): mixed {
                if (!Conversion::hasMDTDungeonName($dungeon->key)) {
                    return null;
                }

                return Floor::where('dungeon_id', $dungeon->id)->where('facade', false)->exists() ?: null;
            },
        );

        $nonFacadeFloors = Floor::where('dungeon_id', $dungeon->id)->where('facade', false)->get();

        $mdtDungeon = $this->getMockBuilderPublic(MDTDungeon::class)
            ->setConstructorArgs([
                app(CacheServiceInterface::class),
                app(CoordinatesServiceInterface::class),
                $dungeon,
            ])
            ->onlyMethods(['getMDTNPCs'])
            ->getMock();
        // Bypass MDT's Lua entirely - the facade check runs regardless of what it returns.
        $mdtDungeon->method('getMDTNPCs')->willReturn(new Collection());

        // Assert
        $this->expectException(FacadeNotConfiguredException::class);

        // Act
        $mdtDungeon->getClonesAsEnemies($mappingVersion, $nonFacadeFloors);
    }
}
