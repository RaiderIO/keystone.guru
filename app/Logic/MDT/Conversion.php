<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 15-1-2019
 * Time: 16:34
 */

namespace App\Logic\MDT;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Service\Season\SeasonService;
use Exception;

class Conversion
{
    const EXPANSION_NAME_MAPPING = [
        Expansion::EXPANSION_LEGION      => 'Legion',
        Expansion::EXPANSION_BFA         => 'BattleForAzeroth',
        Expansion::EXPANSION_SHADOWLANDS => 'Shadowlands',
    ];

    const DUNGEON_NAME_MAPPING = [
        Expansion::EXPANSION_LEGION => [
            Dungeon::DUNGEON_ARCWAY                      => 'TheArcway',
            Dungeon::DUNGEON_BLACK_ROOK_HOLD             => 'BlackRookHold',
            Dungeon::DUNGEON_CATHEDRAL_OF_ETERNAL_NIGHT  => 'CathedralOfEternalNight',
            Dungeon::DUNGEON_COURT_OF_STARS              => 'CourtOfStars',
            Dungeon::DUNGEON_DARKHEART_THICKET           => 'DarkheartThicket',
            Dungeon::DUNGEON_EYE_OF_AZSHARA              => 'EyeOfAzshara',
            Dungeon::DUNGEON_HALLS_OF_VALOR              => 'HallsofValor',
            Dungeon::DUNGEON_LOWER_KARAZHAN              => 'ReturntoKarazhanLower',
            Dungeon::DUNGEON_MAW_OF_SOULS                => 'MawOfSouls',
            Dungeon::DUNGEON_NELTHARIONS_LAIR            => 'NeltharionsLair',
            Dungeon::DUNGEON_UPPER_KARAZHAN              => 'ReturntoKarazhanUpper',
            Dungeon::DUNGEON_THE_SEAT_OF_THE_TRIUMVIRATE => 'SeatoftheTriumvirate',
            Dungeon::DUNGEON_VAULT_OF_THE_WARDENS        => 'VaultoftheWardens',
        ],

        Expansion::EXPANSION_BFA => [
            Dungeon::DUNGEON_ATAL_DAZAR           => 'AtalDazar',
            Dungeon::DUNGEON_FREEHOLD             => 'Freehold',
            Dungeon::DUNGEON_KINGS_REST           => 'KingsRest',
            Dungeon::DUNGEON_SHRINE_OF_THE_STORM  => 'ShrineoftheStorm',
            Dungeon::DUNGEON_SIEGE_OF_BORALUS     => 'SiegeofBoralus',
            Dungeon::DUNGEON_TEMPLE_OF_SETHRALISS => 'TempleofSethraliss',
            Dungeon::DUNGEON_THE_MOTHERLODE       => 'TheMotherlode',
            Dungeon::DUNGEON_THE_UNDERROT         => 'TheUnderrot',
            Dungeon::DUNGEON_TOL_DAGOR            => 'TolDagor',
            Dungeon::DUNGEON_WAYCREST_MANOR       => 'WaycrestManor',
            Dungeon::DUNGEON_MECHAGON_JUNKYARD    => 'MechagonIsland',
            Dungeon::DUNGEON_MECHAGON_WORKSHOP    => 'MechagonCity',
        ],

        Expansion::EXPANSION_SHADOWLANDS => [
            Dungeon::DUNGEON_DE_OTHER_SIDE         => 'DeOtherSide',
            Dungeon::DUNGEON_HALLS_OF_ATONEMENT    => 'HallsOfAtonement',
            Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE => 'MistsOfTirnaScithe',
            Dungeon::DUNGEON_PLAGUEFALL            => 'Plaguefall',
            Dungeon::DUNGEON_SANGUINE_DEPTHS       => 'SanguineDepths',
            Dungeon::DUNGEON_SPIRES_OF_ASCENSION   => 'SpiresOfAscension',
            Dungeon::DUNGEON_THE_NECROTIC_WAKE     => 'TheNecroticWake',
            Dungeon::DUNGEON_THEATER_OF_PAIN       => 'TheaterOfPain',
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
     * @param string $dungeonKey
     * @return string|null
     */
    public static function getExpansionName(string $dungeonKey): ?string
    {
        $result = null;
        foreach (self::DUNGEON_NAME_MAPPING as $expansionShortName => $dungeons) {
            if (isset($dungeons[$dungeonKey])) {
                $result = $expansionShortName;
                break;
            }
        }
        return $result;
    }

    /**
     * @param string $dungeonKey
     * @return string|null
     */
    public static function getMDTExpansionName(string $dungeonKey): ?string
    {
        return self::EXPANSION_NAME_MAPPING[self::getExpansionName($dungeonKey)];
    }

    /**
     * @param $dungeonKey string
     * @return bool True if MDT has a dungeon name, false if it has not.
     */
    public static function hasMDTDungeonName(string $dungeonKey): bool
    {
        return is_string(self::getMDTDungeonName($dungeonKey));
    }

    /**
     * @param $dungeonKey string
     * @return string|null Gets the MDT version of a dungeon name.
     */
    public static function getMDTDungeonName(string $dungeonKey): ?string
    {
        $result = false;

        $expansionName = self::getExpansionName($dungeonKey);
        if (is_string($expansionName)) {
            $result = self::DUNGEON_NAME_MAPPING[$expansionName][$dungeonKey];
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
        return ['y' => (string)round($latLng['lat'] * 2.185, 1), 'x' => (string)round($latLng['lng'] * 2.185, 1)];
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
