<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 15-1-2019
 * Time: 16:34
 */

namespace App\Logic\MDT;

use App\Models\AffixGroup;
use App\Models\Dungeon;
use App\Service\Season\SeasonService;
use Exception;

class Conversion
{
    const DUNGEON_NAME_MAPPING = [
        'BattleForAzeroth' => [
            // Battle for Azeroth
            'Atal\'Dazar'          => 'AtalDazar',
            'Freehold'             => 'Freehold',
            'Kings\' Rest'         => 'KingsRest',
            'Shrine of the Storm'  => 'ShrineoftheStorm',
            'Siege of Boralus'     => 'SiegeofBoralus',
            'Temple of Sethraliss' => 'TempleofSethraliss',
            'The MOTHERLODE!!'     => 'TheMotherlode',
            'The Underrot'         => 'TheUnderrot',
            'Tol Dagor'            => 'TolDagor',
            'Waycrest Manor'       => 'WaycrestManor',
            'Mechagon: Junkyard'   => 'MechagonIsland',
            'Mechagon: Workshop'   => 'MechagonCity',
        ],

        'Shadowlands' => [
            // Shadowlands
            'De Other Side'         => 'DeOtherSide',
            'Halls of Atonement'    => 'HallsOfAtonement',
            'Mists of Tirna Scithe' => 'MistsOfTirnaScithe',
            'Plaguefall'            => 'Plaguefall',
            'Sanguine Depths'       => 'SanguineDepths',
            'Spires of Ascension'   => 'SpiresOfAscension',
            'The Necrotic Wake'     => 'TheNecroticWake',
            'Theater of Pain'       => 'TheaterOfPain',
        ],
    ];

    /**
     * Rounds a number to the nearest two decimals.
     * @param $nr
     * @return float|int
     */
    private static function _round($nr)
    {
        return (int)($nr * 100) / 100;
    }

    /**
     * @param string $dungeonName
     * @return string|null
     */
    public static function getExpansionName(string $dungeonName): ?string
    {
        $result = null;
        foreach (self::DUNGEON_NAME_MAPPING as $expansionName => $dungeons) {
            if (isset($dungeons[$dungeonName])) {
                $result = $expansionName;
            }
        }
        return $result;
    }

    /**
     * @param $dungeonName string
     * @return bool True if MDT has a dungeon name, false if it has not.
     */
    public static function hasMDTDungeonName(string $dungeonName): bool
    {
        return is_string(self::getMDTDungeonName($dungeonName));
    }

    /**
     * @param $dungeonName string
     * @return string|null Gets the MDT version of a dungeon name.
     */
    public static function getMDTDungeonName(string $dungeonName): ?string
    {
        $result = false;

        $expansionName = self::getExpansionName($dungeonName);
        if (is_string($expansionName)) {
            $result = self::DUNGEON_NAME_MAPPING[$expansionName][$dungeonName];
        }

        return $result;
    }

    /**
     * Converts a MDT Dungeon ID to a Keystone.guru ID.
     * @param $mdtDungeonId int
     * @return int
     * @throws Exception An exception if the found dungeon ID was incorrect/not supported.
     */
    public static function convertMDTDungeonID(int $mdtDungeonId): int
    {
        $dungeon = Dungeon::where('mdt_id', $mdtDungeonId)->first();
        if ($dungeon instanceof Dungeon) {
            return $dungeon->id;
        } else {
            throw new Exception('Unsupported dungeon found.');
        }
    }

    /**
     * Converts an array with x/y keys set to an array with lat/lng set, converted to our own coordinate system.
     * @param $xy array
     * @return array
     */
    public static function convertMDTCoordinateToLatLng(array $xy): array
    {
        // This seems to match my coordinate system for about 99%. Needs some more refinement but it should be very minor.
        // Yes I know about php's round() function but it gives floating point rounding errors.
        return ['lat' => self::_round($xy['y'] / 2.185), 'lng' => self::_round($xy['x'] / 2.185)];
    }

    /**
     * Converts an array with lat/lng keys set to an array with x/y set, converted to MDT coordinate system.
     * @param $latLng array
     * @return array
     */
    public static function convertLatLngToMDTCoordinate(array $latLng): array
    {
        return ['y' => $latLng['lat'] * 2.185, 'x' => $latLng['lng'] * 2.185];
    }

    /**
     * Convert a MDT week to a matching affix group
     * @param SeasonService $seasonService
     * @param int $mdtWeek
     * @return AffixGroup
     */
    public static function convertWeekToAffixGroup(SeasonService $seasonService, int $mdtWeek): AffixGroup
    {
        // You can do this in a mathy way but tbh I can't be bothered right now.
        $weekMapping = [
            1  => 4,
            2  => 5,
            3  => 6,
            4  => 7,
            5  => 8,
            6  => 9,
            7  => 10,
            8  => 11,
            9  => 12,
            10 => 1,
            11 => 2,
            12 => 3,
        ];
        return AffixGroup::find($weekMapping[$mdtWeek] + (($seasonService->getSeasons()->count() - 1) * config('keystoneguru.season_iteration_affix_group_count')));
    }

    /**
     * @param SeasonService $seasonService
     * @param AffixGroup $affixGroup
     * @return int
     */
    public static function convertAffixGroupToWeek(SeasonService $seasonService, AffixGroup $affixGroup): int
    {
        // We need to figure out which week it is in the rotation
        $weekIndex = $affixGroup->id % config('keystoneguru.season_iteration_affix_group_count');

        // KG to MDT
        $weekMapping = [
            3  => 11,
            4  => 12,
            5  => 1,
            6  => 2,
            7  => 3,
            8  => 4,
            9  => 5,
            10 => 6,
            11 => 7,
            0  => 8,
            1  => 9,
            2  => 10,
        ];

        return $weekMapping[$weekIndex];
    }
}
