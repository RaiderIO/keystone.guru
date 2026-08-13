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
use App\Models\DungeonKey;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\RaidKey;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Exception;

class Conversion
{
    public const array EXPANSION_NAME_MAPPING = [
        Expansion::EXPANSION_CLASSIC   => 'ClassicEra',
        Expansion::EXPANSION_TBC       => null,
        Expansion::EXPANSION_WOTLK     => 'WrathOfTheLichKing',
        Expansion::EXPANSION_CATACLYSM => 'Cataclysm',
        Expansion::EXPANSION_MOP       => 'MistsOfPandaria',
        Expansion::EXPANSION_WOD       => 'Shadowlands',
        // WoD dungeons are under Shadowlands for latest MDT
        Expansion::EXPANSION_LEGION       => 'Legion',
        Expansion::EXPANSION_BFA          => 'BattleForAzeroth',
        Expansion::EXPANSION_SHADOWLANDS  => 'Shadowlands',
        Expansion::EXPANSION_DRAGONFLIGHT => 'Dragonflight',
        // DF S1 has MoP/WoD dungeons under here
        Expansion::EXPANSION_TWW      => 'TheWarWithin',
        Expansion::EXPANSION_MIDNIGHT => 'Midnight',
        Expansion::EXPANSION_TLT      => 'TheLastTitan',
    ];

    // @formatter:off
    public const array DUNGEON_NAME_MAPPING = [
        //        Expansion::EXPANSION_CLASSIC => [
        //            DungeonKey::BLACKFATHOM_DEEPS->value           => 'BlackfathomDeeps',
        //            DungeonKey::BLACKROCK_DEPTHS->value            => 'BlackrockDepths',
        //            DungeonKey::DEADMINES->value                   => 'Deadmines',
        //            DungeonKey::DIRE_MAUL_WEST->value              => 'DireMaulWest',
        //            DungeonKey::DIRE_MAUL_NORTH->value             => 'DireMaulNorth',
        //            DungeonKey::DIRE_MAUL_EAST->value              => 'DireMaulEast',
        //            DungeonKey::GNOMEREGAN->value                  => 'Gnomeregan',
        //            DungeonKey::LOWER_BLACKROCK_SPIRE->value       => 'LowerBlackrockSpire',
        //            DungeonKey::MARAUDON->value                    => 'Maraudon',
        //            DungeonKey::RAGEFIRE_CHASM->value              => 'RagefireChasm',
        //            DungeonKey::RAZORFEN_DOWNS->value              => 'RazorfenDowns',
        //            DungeonKey::RAZORFEN_KRAUL->value              => 'RazorfenKraul',
        //            DungeonKey::SCARLET_MONASTERY_ARMORY->value    => 'ScarletMonasteryArmory',
        //            DungeonKey::SCARLET_MONASTERY_CATHEDRAL->value => 'ScarletMonasteryCathedral',
        //            DungeonKey::SCARLET_MONASTERY_LIBRARY->value   => 'ScarletMonasteryLibrary',
        //            DungeonKey::SCARLET_MONASTERY_GRAVEYARD->value => 'ScarletMonasteryGraveyard',
        //            DungeonKey::SCHOLOMANCE->value                 => 'Scholomance',
        //            DungeonKey::SHADOWFANG_KEEP->value             => 'ShadowfangKeep',
        //            DungeonKey::STRATHOLME->value                  => 'Stratholme',
        //            DungeonKey::THE_STOCKADE->value                => 'TheStockade',
        //            DungeonKey::THE_TEMPLE_OF_ATAL_HAKKAR->value   => 'TheTempleOfAtalHakkar',
        //            DungeonKey::ULDAMAN->value                     => 'Uldaman',
        //            DungeonKey::UPPER_BLACKROCK_SPIRE->value       => 'UpperBlackrockSpire',
        //            DungeonKey::WAILING_CAVERNS->value             => 'WailingCaverns',
        //            DungeonKey::ZUL_FARRAK->value                  => 'ZulFarrak',
        //        ],
        // Never actually got merged into main
        //        Expansion::EXPANSION_WOTLK => [
        //            DungeonKey::AHN_KAHET_THE_OLD_KINGDOM->value => 'AhnKahetTheOldKingdom',
        //            DungeonKey::AZJOL_NERUB->value               => 'AzjolNerub',
        //            DungeonKey::DRAK_THARON_KEEP->value          => 'DrakTharonKeep',
        //            DungeonKey::GUNDRAK->value                   => 'Gundrak',
        //            DungeonKey::HALLS_OF_LIGHTNING->value        => 'HallsOfLightning',
        //            DungeonKey::HALLS_OF_STONE->value            => 'HallsOfStone',
        //            RaidKey::NAXXRAMAS->value                    => 'Naxxramas',
        //            RaidKey::ULDUAR->value                       => 'Ulduar',
        //            DungeonKey::THE_CULLING_OF_STRATHOLME->value => 'TheCullingOfStratholme',
        //            DungeonKey::THE_NEXUS->value                 => 'TheNexus',
        //            DungeonKey::THE_OCULUS->value                => 'TheOculus',
        //            DungeonKey::THE_VIOLET_HOLD->value           => 'TheVioletHold',
        //            DungeonKey::UTGARDE_KEEP->value              => 'UtgardeKeep',
        //            DungeonKey::UTGARDE_PINNACLE->value          => 'UtgardePinnacle',
        //        ],

        Expansion::EXPANSION_CATACLYSM => [
            //            DungeonKey::THE_VORTEX_PINNACLE->value => 'TheVortexPinnacle',
        ],

        // MDT 6.2 (ptr12.1) deleted the MistsOfPandaria folder from the mainline package, and MDT_Legacy
        // never carried it, so there is no lua for these dungeons in either package any more. Keeping them
        // mapped would make hasMDTDungeonName() lie and every caller throw "Unable to find file"; the
        // existing mapping data in Keystone.guru is untouched, only re-importing from MDT is gone.
        Expansion::EXPANSION_MOP => [
            //            DungeonKey::GATE_OF_THE_SETTING_SUN->value    => 'GateOfTheSettingSun',
            //            DungeonKey::MOGU_SHAN_PALACE->value           => 'MoguShanPalace',
            //            DungeonKey::SCARLET_HALLS_MOP->value          => 'ScarletHalls',
            //            DungeonKey::SCARLET_MONASTERY_MOP->value      => 'ScarletMonastery',
            //            DungeonKey::SCHOLOMANCE_MOP->value            => 'Scholomance',
            //            DungeonKey::SHADO_PAN_MONASTERY->value        => 'ShadoPanMonastery',
            //            DungeonKey::SIEGE_OF_NIUZAO_TEMPLE->value     => 'SiegeOfNiuzaoTemple',
            //            DungeonKey::STORMSTOUT_BREWERY->value         => 'StormstoutBrewery',
            //            DungeonKey::TEMPLE_OF_THE_JADE_SERPENT->value => 'TempleOfTheJadeSerpent',
        ],

        Expansion::EXPANSION_WOD => [
            //            DungeonKey::GRIMRAIL_DEPOT->value            => 'GrimrailDepot',
            //            DungeonKey::IRON_DOCKS->value                => 'IronDocks',
            //            DungeonKey::SHADOWMOON_BURIAL_GROUNDS->value  => 'ShadowmoonBurialGrounds',
        ],

        Expansion::EXPANSION_LEGION => [
            DungeonKey::ARCWAY->value => 'TheArcway',
            //            DungeonKey::BLACK_ROOK_HOLD->value             => 'BlackRookHold',
            DungeonKey::CATHEDRAL_OF_ETERNAL_NIGHT->value => 'CathedralOfEternalNight',
            DungeonKey::COURT_OF_STARS->value             => 'CourtOfStars',
            DungeonKey::DARKHEART_THICKET->value          => 'DarkheartThicket',
            DungeonKey::EYE_OF_AZSHARA->value             => 'EyeOfAzshara',
            DungeonKey::HALLS_OF_VALOR->value             => 'HallsofValor',
            DungeonKey::LOWER_KARAZHAN->value             => 'ReturntoKarazhanLower',
            DungeonKey::MAW_OF_SOULS->value               => 'MawOfSouls',
            DungeonKey::NELTHARIONS_LAIR->value           => 'NeltharionsLair',
            DungeonKey::UPPER_KARAZHAN->value             => 'ReturntoKarazhanUpper',
            //            DungeonKey::THE_SEAT_OF_THE_TRIUMVIRATE->value => 'SeatoftheTriumvirate',
            DungeonKey::VAULT_OF_THE_WARDENS->value => 'VaultoftheWardens',
        ],

        Expansion::EXPANSION_BFA => [
            DungeonKey::ATAL_DAZAR->value => 'AtalDazar',
            DungeonKey::FREEHOLD->value   => 'Freehold',
            // Moved into MDT's Midnight/ folder for 12.1 - see the Midnight block below
            //            DungeonKey::KINGS_REST->value           => 'KingsRest',
            DungeonKey::SIEGE_OF_BORALUS->value    => 'SiegeofBoralus',
            DungeonKey::SHRINE_OF_THE_STORM->value => 'ShrineoftheStorm',
            // Moved into MDT's Midnight/ folder for 12.1 - see the Midnight block below
            //            DungeonKey::TEMPLE_OF_SETHRALISS->value => 'TempleofSethraliss',
            DungeonKey::THE_MOTHERLODE->value    => 'TheMotherlode',
            DungeonKey::THE_UNDERROT->value      => 'TheUnderrot',
            DungeonKey::TOL_DAGOR->value         => 'TolDagor',
            DungeonKey::WAYCREST_MANOR->value    => 'WaycrestManor',
            DungeonKey::MECHAGON_JUNKYARD->value => 'MechagonIsland',
            DungeonKey::MECHAGON_WORKSHOP->value => 'MechagonWorkshop',
        ],

        Expansion::EXPANSION_SHADOWLANDS => [
            // WoD
            DungeonKey::GRIMRAIL_DEPOT->value => 'GrimrailDepot',
            DungeonKey::IRON_DOCKS->value     => 'IronDocks',
            // SL
            DungeonKey::DE_OTHER_SIDE->value              => 'DeOtherSide',
            DungeonKey::HALLS_OF_ATONEMENT->value         => 'HallsOfAtonement',
            DungeonKey::PLAGUEFALL->value                 => 'Plaguefall',
            DungeonKey::SANGUINE_DEPTHS->value            => 'SanguineDepths',
            DungeonKey::SPIRES_OF_ASCENSION->value        => 'SpiresOfAscension',
            DungeonKey::THEATER_OF_PAIN->value            => 'TheaterOfPain',
            DungeonKey::THE_NECROTIC_WAKE->value          => 'TheNecroticWake',
            DungeonKey::TAZAVESH_STREETS_OF_WONDER->value => 'TazaveshLower',
            DungeonKey::TAZAVESH_SO_LEAHS_GAMBIT->value   => 'TazaveshUpper',
        ],

        Expansion::EXPANSION_DRAGONFLIGHT => [
            // Cata
            DungeonKey::THE_VORTEX_PINNACLE->value => 'TheVortexPinnacle',
            // MoP - MDT 6.2 dropped the MistsOfPandaria folder, but MDT_Legacy still ships this one under
            // Dragonflight, so it keeps full MDT support (it is deliberately absent from
            // MAINLINE_MDT_DUNGEONS so it resolves to the legacy package).
            DungeonKey::TEMPLE_OF_THE_JADE_SERPENT->value => 'TempleOfTheJadeSerpent',
            DungeonKey::THRONE_OF_THE_TIDES->value        => 'ThroneOfTides',
            // WoD
            DungeonKey::SHADOWMOON_BURIAL_GROUNDS->value => 'ShadowmoonBurialGrounds',
            DungeonKey::THE_EVERBLOOM->value             => 'Everbloom',
            // Legion
            DungeonKey::BLACK_ROOK_HOLD->value => 'BlackrookHold',
            // BFA
            //            DungeonKey::WAYCREST_MANOR->value => 'WaycrestManor',
            // DF
            // Moved into MDT's Midnight/ folder - see the Midnight block below
            //            DungeonKey::ALGETH_AR_ACADEMY->value  => 'AlgetharAcademy',
            DungeonKey::BRACKENHIDE_HOLLOW->value => 'BrackenhideHollow',
            DungeonKey::HALLS_OF_INFUSION->value  => 'HallsOfInfusion',
            DungeonKey::NELTHARUS->value          => 'Neltharus',
            // Moved into MDT's Midnight/ folder for 12.1 - see the Midnight block below
            //            DungeonKey::RUBY_LIFE_POOLS->value                      => 'RubyLifePools',
            DungeonKey::THE_AZURE_VAULT->value                      => 'TheAzureVault',
            DungeonKey::THE_NOKHUD_OFFENSIVE->value                 => 'TheNokhudOffensive',
            DungeonKey::ULDAMAN_LEGACY_OF_TYR->value                => 'UldamanLegacyOfTyr',
            DungeonKey::DAWN_OF_THE_INFINITE_GALAKRONDS_FALL->value => 'DawnOfTheInfiniteLower',
            DungeonKey::DAWN_OF_THE_INFINITE_MUROZONDS_RISE->value  => 'DawnOfTheInfiniteUpper',
        ],

        Expansion::EXPANSION_TWW => [
            // Cata
            DungeonKey::GRIM_BATOL->value => 'GrimBatol',

            // TWW
            DungeonKey::ARA_KARA_CITY_OF_ECHOES->value    => 'AraKara',
            DungeonKey::CITY_OF_THREADS->value            => 'CityOfThreads',
            DungeonKey::THE_DAWNBREAKER->value            => 'TheDawnbreaker',
            DungeonKey::THE_STONEVAULT->value             => 'TheStonevault',
            DungeonKey::CINDERBREW_MEADERY->value         => 'CinderbrewMeadery',
            DungeonKey::DARKFLAME_CLEFT->value            => 'DarkflameCleft',
            DungeonKey::PRIORY_OF_THE_SACRED_FLAME->value => 'PrioryOfTheSacredFlame',
            DungeonKey::THE_ROOKERY->value                => 'TheRookery',
            DungeonKey::OPERATION_FLOODGATE->value        => 'OperationFloodgate',
            DungeonKey::ECO_DOME_AL_DANI->value           => 'EcoDomeAldani',

        ],

        Expansion::EXPANSION_MIDNIGHT => [
            // Wrath of the Lich King
            DungeonKey::PIT_OF_SARON->value => 'PitOfSaron',

            // Warlords of Draenor
            DungeonKey::SKYREACH->value => 'Skyreach',

            // Legion
            DungeonKey::THE_SEAT_OF_THE_TRIUMVIRATE->value => 'SeatoftheTriumvirate',

            // Battle for Azeroth (moved out of MDT_Legacy into mainline Midnight/ for 12.1)
            DungeonKey::KINGS_REST->value           => 'KingsRest',
            DungeonKey::TEMPLE_OF_SETHRALISS->value => 'TempleOfSethraliss',

            // Dragonflight (but Midnight version)
            DungeonKey::ALGETH_AR_ACADEMY->value          => 'AlgetharAcademy',
            DungeonKey::ALGETH_AR_ACADEMY_MIDNIGHT->value => 'AlgetharAcademy',
            // Dragonflight (moved out of MDT_Legacy into mainline Midnight/ for 12.1)
            DungeonKey::RUBY_LIFE_POOLS->value => 'RubyLifePools',

            // Midnight
            DungeonKey::ALTAR_OF_FANGS->value             => 'AltarOfFangs',
            DungeonKey::DEN_OF_NALORAKK->value            => 'DenOfNalorakk',
            DungeonKey::MAGISTERS_TERRACE_MIDNIGHT->value => 'MagistersTerrace',
            DungeonKey::MAISARA_CAVERNS->value            => 'MaisaraCaverns',
            DungeonKey::MURDER_ROW->value                 => 'MurderRow',
            DungeonKey::NEXUS_POINT_XENAS->value          => 'NexusPointXenas',
            DungeonKey::THE_BLINDING_VALE->value          => 'TheBlindingVale',
            DungeonKey::VOIDSCAR_ARENA->value             => 'VoidscarArena',
            DungeonKey::WINDRUNNER_SPIRE->value           => 'WindrunnerSpire',
        ],
    ];
    // @formatter:on

    private const array MAINLINE_MDT_DUNGEONS = [
        // Mists of Pandaria - removed from the mainline package in MDT 6.2, see DUNGEON_NAME_MAPPING above
        //        DungeonKey::GATE_OF_THE_SETTING_SUN->value,
        //        DungeonKey::MOGU_SHAN_PALACE->value,
        //        DungeonKey::SCARLET_HALLS_MOP->value,
        //        DungeonKey::SCARLET_MONASTERY_MOP->value,
        //        DungeonKey::SCHOLOMANCE_MOP->value,
        //        DungeonKey::SHADO_PAN_MONASTERY->value,
        //        DungeonKey::SIEGE_OF_NIUZAO_TEMPLE->value,
        //        DungeonKey::STORMSTOUT_BREWERY->value,
        //        DungeonKey::TEMPLE_OF_THE_JADE_SERPENT->value,

        // Wrath of the Lich King
        DungeonKey::PIT_OF_SARON->value,

        // Warlords of Draenor
        DungeonKey::SKYREACH->value,

        // Legion
        DungeonKey::THE_SEAT_OF_THE_TRIUMVIRATE->value,

        // Battle for Azeroth (moved out of MDT_Legacy into mainline Midnight/ for 12.1)
        DungeonKey::KINGS_REST->value,
        DungeonKey::TEMPLE_OF_SETHRALISS->value,

        // Dragonflight (but Midnight version). Both the Dragonflight dungeon and its Midnight variant
        // resolve to Midnight/AlgetharAcademy.lua - MDT_Legacy no longer ships a Dragonflight copy.
        DungeonKey::ALGETH_AR_ACADEMY->value,
        DungeonKey::ALGETH_AR_ACADEMY_MIDNIGHT->value,
        // Dragonflight (moved out of MDT_Legacy into mainline Midnight/ for 12.1)
        DungeonKey::RUBY_LIFE_POOLS->value,

        // Midnight
        DungeonKey::ALTAR_OF_FANGS->value,
        DungeonKey::DEN_OF_NALORAKK->value,
        DungeonKey::MAGISTERS_TERRACE_MIDNIGHT->value,
        DungeonKey::MAISARA_CAVERNS->value,
        DungeonKey::MURDER_ROW->value,
        DungeonKey::NEXUS_POINT_XENAS->value,
        DungeonKey::THE_BLINDING_VALE->value,
        DungeonKey::VOIDSCAR_ARENA->value,
        DungeonKey::WINDRUNNER_SPIRE->value,
    ];

    /**
     * Rounds a number to the nearest two decimals.
     */
    private static function round(float|int $nr): float
    {
        return ((int)($nr * 100)) / 100;
    }

    public static function getExpansionName(string $dungeonKey): ?string
    {
        return array_find_key(self::DUNGEON_NAME_MAPPING, fn($dungeons) => isset($dungeons[$dungeonKey]));
    }

    /**
     * @param  string $dungeonKey
     * @return bool   True if MDT an expansion name for this dungeon, false if it has not.
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
     * @param  string $dungeonKey
     * @return bool   True if MDT has a dungeon name, false if it has not.
     */
    public static function hasMDTDungeonName(string $dungeonKey): bool
    {
        return is_string(self::getMDTDungeonName($dungeonKey));
    }

    /**
     * @param  string      $dungeonKey
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
     *
     * @return array{x: string, y: string}
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
     *
     * @return array{x: float, y: float}
     */
    public static function convertLatLngToMDTCoordinate(LatLng $latLng): array
    {
        return [
            'y' => round($latLng->getLat() * 2.185, 1),
            'x' => round($latLng->getLng() * 2.185, 1),
        ];
    }

    /**
     * The same conversion as {@see self::convertLatLngToMDTCoordinate()} but without the rounding, so that
     * a calculated coordinate can be compared against one MDT stored at full precision.
     *
     * @return array{x: float, y: float}
     */
    public static function convertLatLngToMDTCoordinateUnrounded(LatLng $latLng): array
    {
        return [
            'x' => $latLng->getLng() * 2.185,
            'y' => $latLng->getLat() * 2.185,
        ];
    }

    /**
     * Convert a MDT week to a matching affix group
     *
     *
     * @throws Exception
     */
    public static function convertWeekToAffixGroup(
        SeasonServiceInterface $seasonService,
        Dungeon                $dungeon,
        ?int                   $mdtWeek,
    ): ?AffixGroup {
        // Some MDT strings don't carry a week at all (e.g. exported without an affix week
        // selected) - callers already fall back to the current affix group when this returns null.
        if ($mdtWeek === null) {
            return null;
        }

        if (!$dungeon->hasMappingVersionWithSeasons()) {
            return null;
        }

        // An MDT week indexes the current live retail affix rotation, so prefer interpreting it
        // against the current active season when the dungeon is part of it. Only fall back to the
        // dungeon's upcoming/most-recent season for dungeons that aren't in the current season
        // (such as legacy dungeons), which keeps their imports deterministic instead of drifting
        // to whichever season last contained the dungeon.
        $season = $seasonService->getCurrentSeasonForDungeon($dungeon) ??
            $seasonService->getUpcomingSeasonForDungeon($dungeon) ??
            $seasonService->getMostRecentSeasonForDungeon($dungeon);

        if ($season === null) {
            logger()->error(sprintf('Unable to find season for dungeon %s', __($dungeon->name)));

            return null;
        }

        // For each season this is different
        $affixGroup = null;
        if ($season->affixGroups->count() !== 0) {
            if ($season->id === Season::SEASON_TWW_S1) {
                $affixGroup = $season->affixGroups->get(($season->start_affix_group_index + $mdtWeek) % $season->affixGroups->count());
            } else {
                $affixGroup = $season->affixGroups->get(($season->start_affix_group_index + ($mdtWeek - 1)) % $season->affixGroups->count());
            }
        }

        // $affixGroup = $season->affixgroups->get(($season->start_affix_group_index - ($mdtWeek - 1)));
        if ($affixGroup === null) {
            logger()->error('Unable to find affix group for mdtWeek - returning first affix group instead', [
                'mdtWeek' => $mdtWeek,
            ]);

            $affixGroup = $season->affixGroups->getNth($season->start_affix_group_index);
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
