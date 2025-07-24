<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 15-1-2019
 * Time: 16:34
 */

namespace App\Logic\MDT;

use App\Logic\Structs\LatLng;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\Season;
use App\Service\Season\SeasonService;
use Exception;

class Conversion
{
    public const EXPANSION_NAME_MAPPING = [
        Expansion::EXPANSION_CLASSIC      => 'ClassicEra',
        Expansion::EXPANSION_TBC          => null,
        Expansion::EXPANSION_WOTLK        => 'WrathOfTheLichKing',
        Expansion::EXPANSION_CATACLYSM    => 'Cataclysm',
        Expansion::EXPANSION_MOP          => null,
        Expansion::EXPANSION_WOD          => 'Shadowlands', // WoD dungeons are under Shadowlands for latest MDT
        Expansion::EXPANSION_LEGION       => 'Legion',
        Expansion::EXPANSION_BFA          => 'BattleForAzeroth',
        Expansion::EXPANSION_SHADOWLANDS  => 'Shadowlands',
        Expansion::EXPANSION_DRAGONFLIGHT => 'Dragonflight', // DF S1 has MoP/WoD dungeons under here
        Expansion::EXPANSION_TWW          => 'TheWarWithin',
    ];

    // @formatter:off
    public const DUNGEON_NAME_MAPPING = [
        //        Expansion::EXPANSION_CLASSIC => [
        //            Dungeon::DUNGEON_BLACKFATHOM_DEEPS           => 'BlackfathomDeeps',
        //            Dungeon::DUNGEON_BLACKROCK_DEPTHS            => 'BlackrockDepths',
        //            Dungeon::DUNGEON_DEADMINES                   => 'Deadmines',
        //            Dungeon::DUNGEON_DIRE_MAUL_WEST              => 'DireMaulWest',
        //            Dungeon::DUNGEON_DIRE_MAUL_NORTH             => 'DireMaulNorth',
        //            Dungeon::DUNGEON_DIRE_MAUL_EAST              => 'DireMaulEast',
        //            Dungeon::DUNGEON_GNOMEREGAN                  => 'Gnomeregan',
        //            Dungeon::DUNGEON_LOWER_BLACKROCK_SPIRE       => 'LowerBlackrockSpire',
        //            Dungeon::DUNGEON_MARAUDON                    => 'Maraudon',
        //            Dungeon::DUNGEON_RAGEFIRE_CHASM              => 'RagefireChasm',
        //            Dungeon::DUNGEON_RAZORFEN_DOWNS              => 'RazorfenDowns',
        //            Dungeon::DUNGEON_RAZORFEN_KRAUL              => 'RazorfenKraul',
        //            Dungeon::DUNGEON_SCARLET_MONASTERY_ARMORY    => 'ScarletMonasteryArmory',
        //            Dungeon::DUNGEON_SCARLET_MONASTERY_CATHEDRAL => 'ScarletMonasteryCathedral',
        //            Dungeon::DUNGEON_SCARLET_MONASTERY_LIBRARY   => 'ScarletMonasteryLibrary',
        //            Dungeon::DUNGEON_SCARLET_MONASTERY_GRAVEYARD => 'ScarletMonasteryGraveyard',
        //            Dungeon::DUNGEON_SCHOLOMANCE                 => 'Scholomance',
        //            Dungeon::DUNGEON_SHADOWFANG_KEEP             => 'ShadowfangKeep',
        //            Dungeon::DUNGEON_STRATHOLME                  => 'Stratholme',
        //            Dungeon::DUNGEON_THE_STOCKADE                => 'TheStockade',
        //            Dungeon::DUNGEON_THE_TEMPLE_OF_ATAL_HAKKAR   => 'TheTempleOfAtalHakkar',
        //            Dungeon::DUNGEON_ULDAMAN                     => 'Uldaman',
        //            Dungeon::DUNGEON_UPPER_BLACKROCK_SPIRE       => 'UpperBlackrockSpire',
        //            Dungeon::DUNGEON_WAILING_CAVERNS             => 'WailingCaverns',
        //            Dungeon::DUNGEON_ZUL_FARRAK                  => 'ZulFarrak',
        //        ],
        // Never actually got merged into main
        //        Expansion::EXPANSION_WOTLK => [
        //            Dungeon::DUNGEON_AHN_KAHET_THE_OLD_KINGDOM => 'AhnKahetTheOldKingdom',
        //            Dungeon::DUNGEON_AZJOL_NERUB               => 'AzjolNerub',
        //            Dungeon::DUNGEON_DRAK_THARON_KEEP          => 'DrakTharonKeep',
        //            Dungeon::DUNGEON_GUNDRAK                   => 'Gundrak',
        //            Dungeon::DUNGEON_HALLS_OF_LIGHTNING        => 'HallsOfLightning',
        //            Dungeon::DUNGEON_HALLS_OF_STONE            => 'HallsOfStone',
        //            Dungeon::RAID_NAXXRAMAS                    => 'Naxxramas',
        //            Dungeon::RAID_ULDUAR                       => 'Ulduar',
        //            Dungeon::DUNGEON_THE_CULLING_OF_STRATHOLME => 'TheCullingOfStratholme',
        //            Dungeon::DUNGEON_THE_NEXUS                 => 'TheNexus',
        //            Dungeon::DUNGEON_THE_OCULUS                => 'TheOculus',
        //            Dungeon::DUNGEON_THE_VIOLET_HOLD           => 'TheVioletHold',
        //            Dungeon::DUNGEON_UTGARDE_KEEP              => 'UtgardeKeep',
        //            Dungeon::DUNGEON_UTGARDE_PINNACLE          => 'UtgardePinnacle',
        //        ],

        Expansion::EXPANSION_CATACLYSM => [
            //            Dungeon::DUNGEON_THE_VORTEX_PINNACLE => 'TheVortexPinnacle',
        ],

        Expansion::EXPANSION_MOP => [
//            Dungeon::DUNGEON_GATE_OF_THE_SETTING_SUN    => 'GateoftheSettingSun',
//            Dungeon::DUNGEON_MOGU_SHAN_PALACE           => 'MoguShanPalace',
//            Dungeon::DUNGEON_SCARLET_HALLS_MOP          => 'ScarletHalls',
//            Dungeon::DUNGEON_SCARLET_MONASTERY_MOP      => 'ScarletMonastery',
//            Dungeon::DUNGEON_SCHOLOMANCE_MOP            => 'Scholomance',
//            Dungeon::DUNGEON_SHADO_PAN_MONASTERY        => 'ShadoPanMonastery',
//            Dungeon::DUNGEON_SIEGE_OF_NIUZAO_TEMPLE     => 'SiegeOfNiuzaoTemple',
//            Dungeon::DUNGEON_STORMSTOUT_BREWERY         => 'StormstoutBrewery',
//            Dungeon::DUNGEON_TEMPLE_OF_THE_JADE_SERPENT => 'TempleOfTheJadeSerpent',
        ],

        Expansion::EXPANSION_WOD => [
            //            Dungeon::DUNGEON_GRIMRAIL_DEPOT            => 'GrimrailDepot',
            //            Dungeon::DUNGEON_IRON_DOCKS                => 'IronDocks',
            //            Dungeon::DUNGEON_SHADOWMOON_BURIAL_GROUNDS  => 'ShadowmoonBurialGrounds',
        ],

        Expansion::EXPANSION_LEGION => [
            Dungeon::DUNGEON_ARCWAY                      => 'TheArcway',
            //            Dungeon::DUNGEON_BLACK_ROOK_HOLD             => 'BlackRookHold',
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
            Dungeon::DUNGEON_TEMPLE_OF_SETHRALISS => 'TempleofSethraliss',
//            Dungeon::DUNGEON_THE_MOTHERLODE       => 'TheMotherlode',
            Dungeon::DUNGEON_THE_UNDERROT         => 'TheUnderrot',
            Dungeon::DUNGEON_TOL_DAGOR            => 'TolDagor',
            //            Dungeon::DUNGEON_WAYCREST_MANOR       => 'WaycrestManor',
            Dungeon::DUNGEON_MECHAGON_JUNKYARD    => 'MechagonIsland',
//            Dungeon::DUNGEON_MECHAGON_WORKSHOP    => 'MechagonCity',
        ],

        Expansion::EXPANSION_SHADOWLANDS => [
            // WoD
            Dungeon::DUNGEON_GRIMRAIL_DEPOT             => 'GrimrailDepot',
            Dungeon::DUNGEON_IRON_DOCKS                 => 'IronDocks',
            // SL
            Dungeon::DUNGEON_DE_OTHER_SIDE              => 'DeOtherSide',
            Dungeon::DUNGEON_HALLS_OF_ATONEMENT         => 'HallsOfAtonement',
            Dungeon::DUNGEON_PLAGUEFALL                 => 'Plaguefall',
            Dungeon::DUNGEON_SANGUINE_DEPTHS            => 'SanguineDepths',
            Dungeon::DUNGEON_SPIRES_OF_ASCENSION        => 'SpiresOfAscension',
//            Dungeon::DUNGEON_THEATER_OF_PAIN            => 'TheaterOfPain',
            Dungeon::DUNGEON_TAZAVESH_STREETS_OF_WONDER => 'TazaveshLower',
            Dungeon::DUNGEON_TAZAVESH_SO_LEAHS_GAMBIT   => 'TazaveshUpper',
        ],

        Expansion::EXPANSION_DRAGONFLIGHT => [
            // Cata
            Dungeon::DUNGEON_THE_VORTEX_PINNACLE                  => 'TheVortexPinnacle',
            // MoP
            Dungeon::DUNGEON_TEMPLE_OF_THE_JADE_SERPENT           => 'TempleOfTheJadeSerpent',
            Dungeon::DUNGEON_THRONE_OF_THE_TIDES                  => 'ThroneOfTides',
            // WoD
            Dungeon::DUNGEON_SHADOWMOON_BURIAL_GROUNDS            => 'ShadowmoonBurialGrounds',
            Dungeon::DUNGEON_THE_EVERBLOOM                        => 'Everbloom',
            // Legion
            Dungeon::DUNGEON_BLACK_ROOK_HOLD                      => 'BlackrookHold',
            // BFA
            Dungeon::DUNGEON_WAYCREST_MANOR                       => 'WaycrestManor',
            // DF
            Dungeon::DUNGEON_ALGETH_AR_ACADEMY                    => 'AlgetharAcademy',
            Dungeon::DUNGEON_BRACKENHIDE_HOLLOW                   => 'BrackenhideHollow',
            Dungeon::DUNGEON_HALLS_OF_INFUSION                    => 'HallsOfInfusion',
            Dungeon::DUNGEON_NELTHARUS                            => 'Neltharus',
            Dungeon::DUNGEON_RUBY_LIFE_POOLS                      => 'RubyLifePools',
            Dungeon::DUNGEON_THE_AZURE_VAULT                      => 'TheAzureVault',
            Dungeon::DUNGEON_THE_NOKHUD_OFFENSIVE                 => 'TheNokhudOffensive',
            Dungeon::DUNGEON_ULDAMAN_LEGACY_OF_TYR                => 'UldamanLegacyOfTyr',
            Dungeon::DUNGEON_DAWN_OF_THE_INFINITE_GALAKRONDS_FALL => 'DawnOfTheInfiniteLower',
            Dungeon::DUNGEON_DAWN_OF_THE_INFINITE_MUROZONDS_RISE  => 'DawnOfTheInfiniteUpper',
        ],

        Expansion::EXPANSION_TWW => [
            // Cata
            Dungeon::DUNGEON_GRIM_BATOL                 => 'GrimBatol',

            // BFA
            Dungeon::DUNGEON_SIEGE_OF_BORALUS           => 'SiegeofBoralus',
            Dungeon::DUNGEON_THE_MOTHERLODE             => 'TheMotherlode',

            // Shadowlands
            Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE      => 'MistsOfTirnaScithe',
            Dungeon::DUNGEON_THE_NECROTIC_WAKE          => 'TheNecroticWake',
            Dungeon::DUNGEON_THEATER_OF_PAIN            => 'TheaterOfPain',
            Dungeon::DUNGEON_MECHAGON_WORKSHOP          => 'MechagonWorkshop',

            // TWW
            Dungeon::DUNGEON_ARA_KARA_CITY_OF_ECHOES    => 'AraKara',
            Dungeon::DUNGEON_CITY_OF_THREADS            => 'CityOfThreads',
            Dungeon::DUNGEON_THE_DAWNBREAKER            => 'TheDawnbreaker',
            Dungeon::DUNGEON_THE_STONEVAULT             => 'TheStonevault',
            Dungeon::DUNGEON_CINDERBREW_MEADERY         => 'CinderbrewMeadery',
            Dungeon::DUNGEON_DARKFLAME_CLEFT            => 'DarkflameCleft',
            Dungeon::DUNGEON_PRIORY_OF_THE_SACRED_FLAME => 'PrioryOfTheSacredFlame',
            Dungeon::DUNGEON_THE_ROOKERY                => 'TheRookery',
            Dungeon::DUNGEON_OPERATION_FLOODGATE        => 'OperationFloodgate',

        ],
    ];
    // @formatter:on

    private const MAINLINE_MDT_DUNGEONS = [
        // Cata
        Dungeon::DUNGEON_GRIM_BATOL,

        // BFA
        Dungeon::DUNGEON_SIEGE_OF_BORALUS,
        Dungeon::DUNGEON_THE_MOTHERLODE,
        Dungeon::DUNGEON_MECHAGON_WORKSHOP,

        // Shadowlands
        Dungeon::DUNGEON_MISTS_OF_TIRNA_SCITHE,
        Dungeon::DUNGEON_THE_NECROTIC_WAKE,
        Dungeon::DUNGEON_THEATER_OF_PAIN,

        // TWW
        Dungeon::DUNGEON_ARA_KARA_CITY_OF_ECHOES,
        Dungeon::DUNGEON_CITY_OF_THREADS,
        Dungeon::DUNGEON_THE_DAWNBREAKER,
        Dungeon::DUNGEON_THE_STONEVAULT,
        Dungeon::DUNGEON_CINDERBREW_MEADERY,
        Dungeon::DUNGEON_DARKFLAME_CLEFT,
        Dungeon::DUNGEON_PRIORY_OF_THE_SACRED_FLAME,
        Dungeon::DUNGEON_THE_ROOKERY,
        Dungeon::DUNGEON_OPERATION_FLOODGATE,
    ];

    /**
     * Rounds a number to the nearest two decimals.
     */
    private static function round($nr): float
    {
        return ((int)($nr * 100)) / 100;
    }

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
     * @return bool True if MDT an expansion name for this dungeon, false if it has not.
     */
    public static function hasMDTExpansionName(string $dungeonKey): bool
    {
        return is_string(self::getMDTExpansionName($dungeonKey));
    }

    public static function getMDTExpansionName(string $dungeonKey): ?string
    {
        return self::EXPANSION_NAME_MAPPING[self::getExpansionName($dungeonKey)] ?? null;
    }

    /**
     * @param string $dungeonKey
     * @return bool True if MDT has a dungeon name, false if it has not.
     */
    public static function hasMDTDungeonName(string $dungeonKey): bool
    {
        return is_string(self::getMDTDungeonName($dungeonKey));
    }

    /**
     * @param string $dungeonKey
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
     *
     * @param int $mdtDungeonId
     *
     * @throws Exception An exception if the found dungeon ID was incorrect/not supported.
     */
    public static function convertMDTDungeonIDToDungeon(int $mdtDungeonId): Dungeon
    {
        $dungeon = Dungeon::where('mdt_id', $mdtDungeonId)->first();
        if ($dungeon instanceof Dungeon) {
            return $dungeon;
        } else {
            throw new Exception(sprintf('Unsupported dungeon found: %s.', $mdtDungeonId));
        }
    }

    /**
     * Converts an array with x/y keys set to an array with lat/lng set, converted to our own coordinate system.
     *
     * @param array{x: float, y: float} $xy
     */
    public static function convertMDTCoordinateToLatLng(array $xy, ?Floor $floor = null): LatLng
    {
        // This seems to match my coordinate system for about 99%. Needs some more refinement, but it should be very minor.
        // Yes I know about php's round() function but it gives floating point rounding errors.
        return new LatLng(self::round($xy['y'] / 2.185), self::round($xy['x'] / 2.185), $floor);
    }

    /**
     * Converts an array with lat/lng keys set to an array with x/y set, converted to MDT coordinate system.
     */
    public static function convertLatLngToMDTCoordinateString(LatLng $latLng): array
    {
        $mdtCoordinate      = self::convertLatLngToMDTCoordinate($latLng);
        $mdtCoordinate['x'] = (string)$mdtCoordinate['x'];
        $mdtCoordinate['y'] = (string)$mdtCoordinate['y'];

        return $mdtCoordinate;
    }

    /**
     * Converts an array with lat/lng keys set to an array with x/y set, converted to MDT coordinate system.
     */
    public static function convertLatLngToMDTCoordinate(LatLng $latLng): array
    {
        return ['y' => round($latLng->getLat() * 2.185, 1), 'x' => round($latLng->getLng() * 2.185, 1)];
    }

    /**
     * Convert a MDT week to a matching affix group
     *
     *
     * @throws Exception
     */
    public static function convertWeekToAffixGroup(SeasonService $seasonService, Dungeon $dungeon, int $mdtWeek): ?AffixGroup
    {
        if (!$dungeon->hasMappingVersionWithSeasons()) {
            return null;
        }

        $season = $seasonService->getUpcomingSeasonForDungeon($dungeon) ??
            $seasonService->getMostRecentSeasonForDungeon($dungeon);

        if ($season === null) {
            logger()->error(sprintf('Unable to find season for dungeon %s', __($dungeon->name)));

            return null;
        }

        // For each season this is different
        if ($season->id === Season::SEASON_TWW_S1) {
            $affixGroup = $season->affixGroups->get(($season->start_affix_group_index + $mdtWeek) % $season->affixGroups->count());
        } else {
            $affixGroup = $season->affixGroups->get(($season->start_affix_group_index + ($mdtWeek - 1)) % $season->affixGroups->count());
        }

        // $affixGroup = $season->affixgroups->get(($season->start_affix_group_index - ($mdtWeek - 1)));
        if ($affixGroup === null) {
            logger()->error('Unable to find affix group for mdtWeek - returning current affix group instead', [
                'mdtWeek' => $mdtWeek,
            ]);

            $affixGroup = $season->getCurrentAffixGroup();
        }

        return $affixGroup;
    }

    public static function convertAffixGroupToWeek(AffixGroup $affixGroup): int
    {
        // For each season this is different
        if ($affixGroup->season_id === Season::SEASON_TWW_S1) {
            return ($affixGroup->id - 2) % $affixGroup->season->affix_group_count;
        }

        // We need to figure out which week it is in the rotation
        return ($affixGroup->id - 1) % $affixGroup->season->affix_group_count;
    }

    public static function isDungeonInMainlineMDT(Dungeon $dungeon): bool
    {
        return in_array($dungeon->key, self::MAINLINE_MDT_DUNGEONS, true);
    }
}
