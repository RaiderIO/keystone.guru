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
        Expansion::EXPANSION_VANILLA      => null,
        Expansion::EXPANSION_TBC          => null,
        Expansion::EXPANSION_WOTLK        => 'WrathOfTheLichKing',
        Expansion::EXPANSION_CATACLYSM    => 'Cataclysm',
        Expansion::EXPANSION_MOP          => null,
        Expansion::EXPANSION_WOD          => 'Shadowlands', // WoD dungeons are under Shadowlands for latest MDT
        Expansion::EXPANSION_LEGION       => 'Legion',
        Expansion::EXPANSION_BFA          => 'BattleForAzeroth',
        Expansion::EXPANSION_SHADOWLANDS  => 'Shadowlands',
        Expansion::EXPANSION_DRAGONFLIGHT => 'Dragonflight', // DF S1 has MoP/WoD dungeons under here
    ];

    const DUNGEON_NAME_MAPPING = [
        Expansion::EXPANSION_WOTLK => [
            Dungeon::DUNGEON_AHN_KAHET_THE_OLD_KINGDOM => 'AhnKahetTheOldKingdom',
            Dungeon::DUNGEON_AZJOL_NERUB               => 'AzjolNerub',
            Dungeon::DUNGEON_DRAK_THARON_KEEP          => 'DrakTharonKeep',
            Dungeon::DUNGEON_GUNDRAK                   => 'Gundrak',
            Dungeon::DUNGEON_HALLS_OF_LIGHTNING        => 'HallsOfLightning',
            Dungeon::DUNGEON_HALLS_OF_STONE            => 'HallsOfStone',
            Dungeon::RAID_NAXXRAMAS                    => 'Naxxramas',
            Dungeon::RAID_ULDUAR                       => 'Ulduar',
            Dungeon::DUNGEON_THE_CULLING_OF_STRATHOLME => 'TheCullingOfStratholme',
            Dungeon::DUNGEON_THE_NEXUS                 => 'TheNexus',
            Dungeon::DUNGEON_THE_OCULUS                => 'TheOculus',
            Dungeon::DUNGEON_THE_VIOLET_HOLD           => 'TheVioletHold',
            Dungeon::DUNGEON_UTGARDE_KEEP              => 'UtgardeKeep',
            Dungeon::DUNGEON_UTGARDE_PINNACLE          => 'UtgardePinnacle',
        ],

        Expansion::EXPANSION_CATACLYSM => [
//            Dungeon::DUNGEON_THE_VORTEX_PINNACLE => 'TheVortexPinnacle',
        ],

        Expansion::EXPANSION_MOP => [
//            Dungeon::DUNGEON_TEMPLE_OF_THE_JADE_SERPENT => 'TempleOfTheJadeSerpent',
        ],

        Expansion::EXPANSION_WOD => [
//            Dungeon::DUNGEON_GRIMRAIL_DEPOT            => 'GrimrailDepot',
//            Dungeon::DUNGEON_IRON_DOCKS                => 'IronDocks',
//            Dungeon::DUNGEON_SHADOWMOON_BURIAL_GROUNDS  => 'ShadowmoonBurialGrounds',
        ],

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
            // WoD
            Dungeon::DUNGEON_GRIMRAIL_DEPOT             => 'GrimrailDepot',
            Dungeon::DUNGEON_IRON_DOCKS                 => 'IronDocks',
            // SL
            Dungeon::DUNGEON_DE_OTHER_SIDE              => 'DeOtherSide',
            Dungeon::DUNGEON_HALLS_OF_ATONEMENT         => 'HallsOfAtonement',
            Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE      => 'MistsOfTirnaScithe',
            Dungeon::DUNGEON_PLAGUEFALL                 => 'Plaguefall',
            Dungeon::DUNGEON_SANGUINE_DEPTHS            => 'SanguineDepths',
            Dungeon::DUNGEON_SPIRES_OF_ASCENSION        => 'SpiresOfAscension',
            Dungeon::DUNGEON_THE_NECROTIC_WAKE          => 'TheNecroticWake',
            Dungeon::DUNGEON_THEATER_OF_PAIN            => 'TheaterOfPain',
            Dungeon::DUNGEON_TAZAVESH_STREETS_OF_WONDER => 'TazaveshLower',
            Dungeon::DUNGEON_TAZAVESH_SO_LEAHS_GAMBIT   => 'TazaveshUpper',
        ],

        Expansion::EXPANSION_DRAGONFLIGHT => [
            // Cata
            Dungeon::DUNGEON_THE_VORTEX_PINNACLE => 'TheVortexPinnacle',
            // MoP
            Dungeon::DUNGEON_TEMPLE_OF_THE_JADE_SERPENT => 'TempleOfTheJadeSerpent',
            // WoD
            Dungeon::DUNGEON_SHADOWMOON_BURIAL_GROUNDS  => 'ShadowmoonBurialGrounds',
            // DF
            Dungeon::DUNGEON_ALGETH_AR_ACADEMY          => 'AlgetharAcademy',
            Dungeon::DUNGEON_BRACKENHIDE_HOLLOW         => 'BrackenhideHollow',
            Dungeon::DUNGEON_HALLS_OF_INFUSION          => 'HallsOfInfusion',
            Dungeon::DUNGEON_NELTHARUS                  => 'Neltharus',
            Dungeon::DUNGEON_RUBY_LIFE_POOLS            => 'RubyLifePools',
            Dungeon::DUNGEON_THE_AZURE_VAULT            => 'TheAzureVault',
            Dungeon::DUNGEON_THE_NOKHUD_OFFENSIVE       => 'TheNokhudOffensive',
            Dungeon::DUNGEON_ULDAMAN_LEGACY_OF_TYR      => 'UldamanLegacyOfTyr',
        ],
    ];

    /**
     * Rounds a number to the nearest two decimals.
     * @param $nr
     * @return int
     */
    private static function round($nr): int
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
        return self::EXPANSION_NAME_MAPPING[self::getExpansionName($dungeonKey)] ?? null;
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
        $result = null;

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
        // This seems to match my coordinate system for about 99%. Needs some more refinement, but it should be very minor.
        // Yes I know about php's round() function but it gives floating point rounding errors.
        return ['lat' => self::round($xy['y'] / 2.185), 'lng' => self::round($xy['x'] / 2.185)];
    }

    /**
     * Converts an array with lat/lng keys set to an array with x/y set, converted to MDT coordinate system.
     * @param $latLng array
     * @return array
     */
    public static function convertLatLngToMDTCoordinateString(array $latLng): array
    {
        $mdtCoordinate      = self::convertLatLngToMDTCoordinate($latLng);
        $mdtCoordinate['x'] = (string)$mdtCoordinate['x'];
        $mdtCoordinate['y'] = (string)$mdtCoordinate['y'];
        return $mdtCoordinate;
    }

    /**
     * Converts an array with lat/lng keys set to an array with x/y set, converted to MDT coordinate system.
     * @param $latLng array
     * @return array
     */
    public static function convertLatLngToMDTCoordinate(array $latLng): array
    {
        return ['y' => round($latLng['lat'] * 2.185, 1), 'x' => round($latLng['lng'] * 2.185, 1)];
    }

    /**
     * Convert a MDT week to a matching affix group
     * @param SeasonService $seasonService
     * @param Dungeon $dungeon
     * @param int $mdtWeek
     * @return AffixGroup|null
     * @throws Exception
     */
    public static function convertWeekToAffixGroup(SeasonService $seasonService, Dungeon $dungeon, int $mdtWeek): ?AffixGroup
    {
        $season = $dungeon->getActiveSeason($seasonService);
        if ($season === null) {
            logger()->error(sprintf('Unable to find season for dungeon %s', __($dungeon->name)));
            return null;
        }

        $affixGroup = $season->affixgroups->get($mdtWeek - 1);
        if ($affixGroup === null) {
            logger()->error('Unable to find affix group for mdtWeek - returning current affix group instead', [
                '$mdtWeek' => $mdtWeek,
            ]);

            $affixGroup = $season->getCurrentAffixGroup();
        }
        return $affixGroup;
    }

    /**
     * @param AffixGroup $affixGroup
     * @return int
     */
    public static function convertAffixGroupToWeek(AffixGroup $affixGroup): int
    {
        // We need to figure out which week it is in the rotation
        return ($affixGroup->id - 1) % $affixGroup->season->affix_group_count;
    }
}
