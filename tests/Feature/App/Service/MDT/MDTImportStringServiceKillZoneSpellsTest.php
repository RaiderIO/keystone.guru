<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Spell\KnownSpell;
use App\Models\Spell\Spell;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTImportStringService')]
#[Group('MDTImportStringServiceKillZoneSpells')]
final class MDTImportStringServiceKillZoneSpellsTest extends MDTImportStringServiceTestBase
{
    /** @var float The importer attaches a note to a pull when one of the pull's enemies is closer than this */
    private const float IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS = 50;

    /** @var array<int, Floor> */
    private array $floorsById = [];

    #[Test]
    public function getDungeonRoute_givenExportedKillZoneWithBloodlust_assignsBloodlustToThePullWithoutMapIcon(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $this->createKillZone($dungeonRoute, 1, [KnownSpell::Bloodlust->value], $this->getSafeMdtEnemies($dungeonRoute)->first());

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertSame([1 => [KnownSpell::Bloodlust->value]], $this->getSpellIdsByKillZoneIndex($importedRoute));
            $this->assertSame(0, $importedRoute->mapIcons()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenExportedKillZonesWithSeveralSpells_assignsEverySpellToItsPull(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyCount: 2);
            $enemies      = $this->getSafeMdtEnemies($dungeonRoute, limit: 2);
            $this->createKillZone($dungeonRoute, 1, [KnownSpell::TimeWarp->value, KnownSpell::Heroism->value], $enemies->get(0));
            $this->createKillZone($dungeonRoute, 2, [], $enemies->get(1));

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertEquals(
                [1 => [KnownSpell::TimeWarp->value, KnownSpell::Heroism->value], 2 => []],
                $this->getSpellIdsByKillZoneIndex($importedRoute),
            );
            $this->assertSame(0, $importedRoute->mapIcons()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenStringExportedAndImportedInTheSameNonEnglishLocale_assignsTheSpells(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;
        $locale        = app()->getLocale();

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $this->createKillZone($dungeonRoute, 1, [KnownSpell::Bloodlust->value], $this->getSafeMdtEnemies($dungeonRoute)->first());

            app()->setLocale('de_DE');
            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertSame([1 => [KnownSpell::Bloodlust->value]], $this->getSpellIdsByKillZoneIndex($importedRoute));
            $this->assertSame(0, $importedRoute->mapIcons()->count());
        } finally {
            app()->setLocale($locale);
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenImportedRouteExportedAgain_exportsTheSpellsNoteOnce(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $this->createKillZone($dungeonRoute, 1, [KnownSpell::Bloodlust->value], $this->getSafeMdtEnemies($dungeonRoute)->first());

            $importedRoute = $this->importStringToDungeonRoute($this->exportDungeonRouteToString($dungeonRoute));

            // Act
            $reExportedString = $this->exportDungeonRouteToString(DungeonRoute::findOrFail($importedRoute->id));

            // Assert
            $objects = array_values($this->decode($reExportedString)['objects'] ?? []);
            $this->assertCount(1, $objects);
            $this->assertSame('Bloodlust', $objects[0]['d'][4]);
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[DataProvider('spellNoteProvider')]
    public function getDungeonRoute_givenNoteNamingASpellOnAPullEnemy_assignsThatSpellToThePull(string $comment, int $expectedSpellId): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute)->first();
            $this->createKillZone($dungeonRoute, 1, [], $enemy);
            $this->createCommentMapIcon($dungeonRoute, $comment, $this->getFloor($enemy->floor_id), $enemy->lat, $enemy->lng);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertSame([1 => [$expectedSpellId]], $this->getSpellIdsByKillZoneIndex($importedRoute));
            $this->assertSame(0, $importedRoute->mapIcons()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function spellNoteProvider(): array
    {
        return [
            'spell name'                 => ['Bloodlust', KnownSpell::Bloodlust->value],
            'upper case with whitespace' => ['  HEROISM ', KnownSpell::Heroism->value],
            'alias'                      => ['timewarp', KnownSpell::TimeWarp->value],
            'alias of a renamed spell'   => ['Fury of the Ancients', KnownSpell::FuryOfTheAspects->value],
            'trailing blank lines'       => ["Primal Rage\n\n", KnownSpell::PrimalRage->value],
        ];
    }

    #[Test]
    public function getDungeonRoute_givenNoteNamingANonBloodlustSelectableSpell_assignsThatSpellToThePull(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            /** @var Spell $spell */
            $spell = Spell::query()
                ->where('selectable', true)
                ->whereNotIn('id', KnownSpell::BLOODLUSTY_SPELLS)
                ->orderBy('id')
                ->get()
                ->firstOrFail(static fn(Spell $spell): bool => __($spell->name, [], 'en_US') !== $spell->name);

            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute)->first();
            $this->createKillZone($dungeonRoute, 1, [], $enemy);
            $this->createCommentMapIcon($dungeonRoute, __($spell->name, [], 'en_US'), $this->getFloor($enemy->floor_id), $enemy->lat, $enemy->lng);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertCount(1, $this->getSpellIdsByKillZoneIndex($importedRoute)[1]);
            $this->assertSame(
                __($spell->name, [], 'en_US'),
                __(Spell::findOrFail($this->getSpellIdsByKillZoneIndex($importedRoute)[1][0])->name, [], 'en_US'),
            );
            $this->assertSame(0, $importedRoute->mapIcons()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenNotesForASpellAlreadyOnThePull_assignsTheSpellOnce(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute)->first();
            $this->createKillZone($dungeonRoute, 1, [KnownSpell::Bloodlust->value], $enemy);
            $this->createCommentMapIcon($dungeonRoute, 'Bloodlust', $this->getFloor($enemy->floor_id), $enemy->lat, $enemy->lng);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertSame([1 => [KnownSpell::Bloodlust->value]], $this->getSpellIdsByKillZoneIndex($importedRoute));
            $this->assertSame(0, $importedRoute->mapIcons()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenNoteMixingASpellNameWithOtherText_createsMapIconInstead(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute)->first();
            $this->createKillZone($dungeonRoute, 1, [], $enemy);
            $this->createCommentMapIcon($dungeonRoute, "Bloodlust\nthen run to the boss", $this->getFloor($enemy->floor_id), $enemy->lat, $enemy->lng);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertSame([1 => []], $this->getSpellIdsByKillZoneIndex($importedRoute));
            $this->assertSame(["Bloodlust\nthen run to the boss"], $importedRoute->mapIcons()->pluck('comment')->all());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenNonSpellNoteOnAPullEnemyAndAssignNotesToPulls_setsTheDescription(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute)->first();
            $this->createKillZone($dungeonRoute, 1, [], $enemy);
            $this->createCommentMapIcon($dungeonRoute, 'Stun the caster', $this->getFloor($enemy->floor_id), $enemy->lat, $enemy->lng);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString, assignNotesToPulls: true);

            // Assert
            $this->assertSame([1 => []], $this->getSpellIdsByKillZoneIndex($importedRoute));
            $this->assertSame('Stun the caster', $importedRoute->killZones()->firstOrFail()->description);
            $this->assertSame(0, $importedRoute->mapIcons()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenSpellNoteFarFromEveryPull_createsMapIconInstead(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyFilter: $this->hasAFarAwayPoint(...));
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute, enemyFilter: $this->hasAFarAwayPoint(...))->first();
            $this->createKillZone($dungeonRoute, 1, [], $enemy);
            $farAwayLatLng = $this->getFarAwayLatLng($enemy);
            $this->createCommentMapIcon($dungeonRoute, 'Bloodlust', $farAwayLatLng->getFloor(), $farAwayLatLng->getLat(), $farAwayLatLng->getLng());

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertSame([1 => []], $this->getSpellIdsByKillZoneIndex($importedRoute));
            $this->assertSame(['Bloodlust'], $importedRoute->mapIcons()->pluck('comment')->all());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenSpellNoteWithinRangeOfTwoPulls_assignsTheSpellToTheNearestPull(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $attempts = 0;
            do {
                $dungeonRoute?->delete();
                $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyCount: 2);
                $enemies      = $this->findTwoEnemiesWithinImportRangeOfEachOther($this->getSafeMdtEnemies($dungeonRoute, limit: 200));
            } while ($enemies === null && ++$attempts < 25);
            $this->assertNotNull($enemies, 'No MDT dungeon has two safe enemies within import range of each other');

            [$fartherEnemy, $nearestEnemy] = $enemies;
            $this->createKillZone($dungeonRoute, 1, [], $fartherEnemy);
            $this->createKillZone($dungeonRoute, 2, [], $nearestEnemy);
            $this->createCommentMapIcon($dungeonRoute, 'Bloodlust', $this->getFloor($nearestEnemy->floor_id), $nearestEnemy->lat, $nearestEnemy->lng);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertSame([1 => [], 2 => [KnownSpell::Bloodlust->value]], $this->getSpellIdsByKillZoneIndex($importedRoute));
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenExportedSpellsPullWithAnotherPullsEnemyRightBelowIt_keepsTheSpellsOnTheirPull(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $attempts = 0;
            do {
                $dungeonRoute?->delete();
                $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyCount: 2);
                $enemies      = $this->findEnemyWithAnotherEnemyRightBelowIt($this->getSafeMdtEnemies($dungeonRoute, limit: 200));
            } while ($enemies === null && ++$attempts < 25);
            $this->assertNotNull($enemies, 'No MDT dungeon has a safe enemy with another one right below it');

            [$upperEnemy, $lowerEnemy] = $enemies;
            $this->createKillZone($dungeonRoute, 1, [KnownSpell::Bloodlust->value], $upperEnemy);
            $this->createKillZone($dungeonRoute, 2, [], $lowerEnemy);

            $encodedString = $this->exportDungeonRouteToString($dungeonRoute);

            // Act
            $importedRoute = $this->importStringToDungeonRoute($encodedString);

            // Assert
            $this->assertSame([1 => [KnownSpell::Bloodlust->value], 2 => []], $this->getSpellIdsByKillZoneIndex($importedRoute));
            $this->assertSame(0, $importedRoute->mapIcons()->count());
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    /**
     * @param array<int, int> $spellIds
     */
    private function createKillZone(DungeonRoute $dungeonRoute, int $index, array $spellIds, Enemy $enemy): KillZone
    {
        return KillZone::factory()
            ->withEnemies($enemy)
            ->withSpells(...$spellIds)
            ->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'index'            => $index,
                'description'      => null,
                'floor_id'         => null,
                'lat'              => null,
                'lng'              => null,
            ]);
    }

    private function createCommentMapIcon(DungeonRoute $dungeonRoute, string $comment, Floor $floor, float $lat, float $lng): MapIcon
    {
        return MapIcon::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'map_icon_type_id' => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            'lat'              => $lat,
            'lng'              => $lng,
            'comment'          => $comment,
        ]);
    }

    /**
     * @return array<int, array<int, int>> The spell IDs assigned to each pull, by pull index.
     */
    private function getSpellIdsByKillZoneIndex(DungeonRoute $dungeonRoute): array
    {
        return $dungeonRoute->killZones()
            ->with('killZoneSpells')
            ->orderBy('index')
            ->get()
            ->mapWithKeys(static fn(KillZone $killZone): array => [
                $killZone->index => $killZone->killZoneSpells->sortBy('id')->pluck('spell_id')->values()->all(),
            ])
            ->all();
    }

    private function getFloor(int $floorId): Floor
    {
        return $this->floorsById[$floorId] ??= Floor::findOrFail($floorId);
    }

    private function getFarAwayLatLng(Enemy $enemy): LatLng
    {
        return new LatLng(
            $enemy->lat > CoordinatesService::MAP_MAX_LAT / 2 ? CoordinatesService::MAP_MAX_LAT + 5 : -5,
            $enemy->lng < CoordinatesService::MAP_MAX_LNG / 2 ? CoordinatesService::MAP_MAX_LNG - 5 : 5,
            $this->getFloor($enemy->floor_id),
        );
    }

    private function hasAFarAwayPoint(Enemy $enemy): bool
    {
        return $this->distanceYards(
            new LatLng($enemy->lat, $enemy->lng, $this->getFloor($enemy->floor_id)),
            $this->getFarAwayLatLng($enemy),
        ) > self::IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS * 2;
    }

    /**
     * @param  Collection<int, Enemy>         $enemies
     * @return array{0: Enemy, 1: Enemy}|null Two enemies on the same floor, apart but both within import range of the second.
     */
    private function findTwoEnemiesWithinImportRangeOfEachOther(Collection $enemies): ?array
    {
        foreach ($enemies->groupBy('floor_id') as $floorId => $enemiesOnFloor) {
            $floor = $this->getFloor((int)$floorId);

            foreach ($enemiesOnFloor as $enemyA) {
                foreach ($enemiesOnFloor as $enemyB) {
                    $distance = $this->distanceYards(
                        new LatLng($enemyA->lat, $enemyA->lng, $floor),
                        new LatLng($enemyB->lat, $enemyB->lng, $floor),
                    );

                    if ($distance > 5 && $distance < self::IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS - 5) {
                        return [$enemyA, $enemyB];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Enemy>         $enemies
     * @return array{0: Enemy, 1: Enemy}|null An enemy, and another enemy on its floor that is closer than the first one
     *                                        to the spot a fixed offset below the first one (6 map units, capped at 25
     *                                        yards ingame).
     */
    private function findEnemyWithAnotherEnemyRightBelowIt(Collection $enemies): ?array
    {
        foreach ($enemies->groupBy('floor_id') as $floorId => $enemiesOnFloor) {
            $floor = $this->getFloor((int)$floorId);

            foreach ($enemiesOnFloor as $upperEnemy) {
                $upperLatLng = new LatLng($upperEnemy->lat, $upperEnemy->lng, $floor);
                $offset      = 6.0;
                $belowLatLng = new LatLng($upperEnemy->lat - $offset, $upperEnemy->lng, $floor);
                $offsetYards = $this->distanceYards($upperLatLng, $belowLatLng);
                if ($offsetYards > 25) {
                    $belowLatLng = new LatLng($upperEnemy->lat - $offset * 25 / $offsetYards, $upperEnemy->lng, $floor);
                }

                if ($belowLatLng->getLat() < CoordinatesService::MAP_MAX_LAT) {
                    continue;
                }

                foreach ($enemiesOnFloor as $lowerEnemy) {
                    if ($lowerEnemy->id === $upperEnemy->id) {
                        continue;
                    }

                    $lowerLatLng = new LatLng($lowerEnemy->lat, $lowerEnemy->lng, $floor);
                    if ($this->distanceYards($belowLatLng, $lowerLatLng) < $this->distanceYards($belowLatLng, $upperLatLng) - 1) {
                        return [$upperEnemy, $lowerEnemy];
                    }
                }
            }
        }

        return null;
    }

    private function distanceYards(LatLng $latLngA, LatLng $latLngB): float
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);

        return $coordinatesService->distanceIngameXY(
            $coordinatesService->calculateIngameLocationForMapLocation($latLngA),
            $coordinatesService->calculateIngameLocationForMapLocation($latLngB),
        );
    }
}
