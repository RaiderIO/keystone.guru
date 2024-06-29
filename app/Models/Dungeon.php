<?php

namespace App\Models;

use App\Logic\MDT\Conversion;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Service\Season\SeasonServiceInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Mockery\Exception;

/**
 * @property int                                    $id The ID of this Dungeon.
 * @property int                                    $expansion_id The linked expansion to this dungeon.
 * @property int                                    $game_version_id The linked game version to this dungeon.
 * @property int                                    $zone_id The ID of the location that WoW has given this dungeon.
 * @property int                                    $map_id The ID of the map (used internally in the game, used for simulation craft purposes)
 * @property int                                    $challenge_mode_id The ID of the M+ for this dungeon (used internally in the game, used for ARC)
 * @property int                                    $mdt_id The ID that MDT has given this dungeon.
 * @property string                                 $name The name of the dungeon.
 * @property string                                 $slug The url friendly slug of the dungeon.
 * @property string                                 $key Shorthand key of the dungeon
 * @property bool                                   $speedrun_enabled True if this dungeon has a speedrun enabled, false if it does not.
 * @property bool                                   $speedrun_difficulty_10_man_enabled True if this dungeon's speedrun is for 10-man.
 * @property bool                                   $speedrun_difficulty_25_man_enabled True if this dungeon's speedrun is for 25-man.
 * @property bool                                   $active True if this dungeon is active, false if it is not.
 * @property bool                                   $mdt_supported True if MDT is supported for this dungeon, false if it is not.
 * @property Expansion                              $expansion
 * @property GameVersion                            $gameVersion
 * @property MappingVersion                         $currentMappingVersion
 * @property Collection<MappingVersion>             $mappingVersions
 * @property Collection<Floor>                      $floors
 * @property Collection<Floor>                      $activeFloors
 * @property Collection<DungeonRoute>               $dungeonRoutes
 * @property Collection<DungeonRoute>               $dungeonRoutesForExport
 * @property Collection<Npc>                        $npcs
 * @property Collection<Enemy>                      $enemies
 * @property Collection<EnemyPack>                  $enemyPacks
 * @property Collection<EnemyPatrol>                $enemyPatrols
 * @property Collection<MapIcon>                    $mapIcons
 * @property Collection<DungeonFloorSwitchMarker>   $dungeonFloorSwitchMarkers
 * @property Collection<MountableArea>              $mountableAreas
 * @property Collection<DungeonSpeedrunRequiredNpc> $dungeonSpeedrunRequiredNpcs10Man
 * @property Collection<DungeonSpeedrunRequiredNpc> $dungeonSpeedrunRequiredNpcs25Man
 *
 * @method static Builder active()
 * @method static Builder inactive()
 *
 * @mixin Eloquent
 */
class Dungeon extends CacheModel implements MappingModelInterface
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['floor_count', 'mdt_supported'];

    protected $fillable = [
        'expansion_id',
        'game_version_id',
        'active',
        'speedrun_enabled',
        'speedrun_difficulty_10_man_enabled',
        'speedrun_difficulty_25_man_enabled',
        'zone_id',
        'map_id',
        'challenge_mode_id',
        'mdt_id',
        'name',
        'key',
        'slug',
    ];

    public $with = ['expansion', 'gameVersion', 'floors'];

    public $hidden = ['slug', 'active', 'mdt_id', 'zone_id', 'created_at', 'updated_at'];

    public $timestamps = false;

    public const DIFFICULTY_10_MAN = 1;

    public const DIFFICULTY_25_MAN = 2;

    public const DIFFICULTY_ALL = [
        self::DIFFICULTY_10_MAN,
        self::DIFFICULTY_25_MAN,
    ];

    // Classic
    public const DUNGEON_BLACKFATHOM_DEEPS           = 'blackfathom_deeps';     //blackfanthomdeeps
    public const DUNGEON_BLACKROCK_DEPTHS            = 'blackrock_depths';      //blackrockdepths
    public const DUNGEON_DEADMINES                   = 'deadmines';             //thedeadmines
    public const DUNGEON_DIRE_MAUL_WEST              = 'dire_maul_west';        //diremaul
    public const DUNGEON_DIRE_MAUL_NORTH             = 'dire_maul_north';       //diremaul
    public const DUNGEON_DIRE_MAUL_EAST              = 'dire_maul_east';        //diremaul
    public const DUNGEON_GNOMEREGAN                  = 'gnomeregan';            //gnomeregan
    public const DUNGEON_LOWER_BLACKROCK_SPIRE       = 'lower_blackrock_spire'; //blackrockspire
    public const DUNGEON_MARAUDON                    = 'maraudon';
    public const DUNGEON_RAGEFIRE_CHASM              = 'ragefire_chasm';              //ragefire
    public const DUNGEON_RAZORFEN_DOWNS              = 'razorfen_downs';              //razorfendowns
    public const DUNGEON_RAZORFEN_KRAUL              = 'razorfen_kraul';              //razorfenkraul
    public const DUNGEON_SCARLET_MONASTERY_ARMORY    = 'scarlet_monastery_armory';    //scarletmonastery
    public const DUNGEON_SCARLET_MONASTERY_CATHEDRAL = 'scarlet_monastery_cathedral'; //scarletmonastery
    public const DUNGEON_SCARLET_MONASTERY_LIBRARY   = 'scarlet_monastery_library';   //scarletmonastery
    public const DUNGEON_SCARLET_MONASTERY_GRAVEYARD = 'scarlet_monastery_graveyard'; //scarletmonastery
    public const DUNGEON_SCHOLOMANCE                 = 'scholomance';
    public const DUNGEON_SHADOWFANG_KEEP             = 'shadowfang_keep'; //shadowfangkeep
    public const DUNGEON_STRATHOLME                  = 'stratholme';
    public const DUNGEON_THE_STOCKADE                = 'the_stockade';              //thestockade
    public const DUNGEON_THE_TEMPLE_OF_ATAL_HAKKAR   = 'the_temple_of_atal_hakkar'; //thetempleofatalhakkar
    public const DUNGEON_ULDAMAN                     = 'uldaman';
    public const DUNGEON_UPPER_BLACKROCK_SPIRE       = 'upper_blackrock_spire'; //upperblackrockspire
    public const DUNGEON_WAILING_CAVERNS             = 'wailing_caverns';       //wailingcaverns
    public const DUNGEON_ZUL_FARRAK                  = 'zul_farrak';            //zulfarrak

    // Classic: Season of Discovery
    public const DUNGEON_GNOMEREGAN_SOD = 'gnomeregan_sod';                     //gnomeregan


    // The Burning Crusade
    public const DUNGEON_ACHENAI_CRYPTS          = 'auchenai_crypts';
    public const DUNGEON_HELLFIRE_RAMPARTS       = 'hellfire_ramparts';
    public const DUNGEON_MAGISTERS_TERRACE       = 'magisters_terrace';
    public const DUNGEON_MANA_TOMBS              = 'mana_tombs';
    public const DUNGEON_OLD_HILLSBRAD_FOOTHILLS = 'old_hillsbrad_foothills';
    public const DUNGEON_SETHEKK_HALLS           = 'sethekk_halls';
    public const DUNGEON_SHADOW_LABYRINTH        = 'shadow_labyrinth';
    public const DUNGEON_THE_ARCATRAZ            = 'the_arcatraz';
    public const DUNGEON_THE_BLACK_MORASS        = 'the_black_morass';
    public const DUNGEON_THE_BLOOD_FURNACE       = 'the_blood_furnace';
    public const DUNGEON_THE_BOTANICA            = 'the_botanica';
    public const DUNGEON_THE_MECHANAR            = 'the_mechanar';
    public const DUNGEON_THE_SHATTERED_HALLS     = 'the_shattered_halls';
    public const DUNGEON_THE_SLAVE_PENS          = 'the_slave_pens';
    public const DUNGEON_THE_STEAMVAULT          = 'the_steamvault';
    public const DUNGEON_THE_UNDERBOG            = 'the_underbog';

    // Wrath of the Lich King
    public const DUNGEON_AHN_KAHET_THE_OLD_KINGDOM = 'ahnkahet';
    public const DUNGEON_AZJOL_NERUB               = 'azjolnerub';
    public const DUNGEON_DRAK_THARON_KEEP          = 'draktharonkeep';
    public const DUNGEON_GUNDRAK                   = 'gundrak';
    public const DUNGEON_HALLS_OF_LIGHTNING        = 'hallsoflightning';
    public const DUNGEON_HALLS_OF_REFLECTION       = 'hallsofreflection';
    public const DUNGEON_HALLS_OF_STONE            = 'hallsofstone'; // ulduar77
    public const DUNGEON_PIT_OF_SARON              = 'pitofsaron';
    public const DUNGEON_THE_CULLING_OF_STRATHOLME = 'thecullingofstratholme'; // cotstratholme
    public const DUNGEON_THE_FORGE_OF_SOULS        = 'theforgeofsouls';
    public const DUNGEON_THE_NEXUS                 = 'thenexus';
    public const DUNGEON_THE_OCULUS                = 'theoculus';          // nexus80
    public const DUNGEON_THE_VIOLET_HOLD           = 'theviolethold';      // violethold
    public const DUNGEON_TRIAL_OF_THE_CHAMPION     = 'trialofthechampion'; // theargentcoliseum
    public const DUNGEON_UTGARDE_KEEP              = 'utgardekeep';
    public const DUNGEON_UTGARDE_PINNACLE          = 'utgardepinnacle';

    // Wrath of the Lich King Raid
    public const RAID_ICECROWN_CITADEL                         = 'icecrowncitadel';
    public const RAID_NAXXRAMAS                                = 'naxxramas';
    public const RAID_ONYXIAS_LAIR                             = 'onyxiaslair';
    public const RAID_CRUSADERS_COLISEUM_TRIAL_OF_THE_CRUSADER = 'theargentcoliseum';
    public const RAID_THE_EYE_OF_ETERNITY                      = 'theeyeofeternity';
    public const RAID_THE_OBSIDIAN_SANCTUM                     = 'theobsidiansanctum';
    public const RAID_THE_RUBY_SANCTUM                         = 'therubysanctum';
    public const RAID_ULDUAR                                   = 'ulduar';
    public const RAID_VAULT_OF_ARCHAVON                        = 'vaultofarchavon';

    // Cataclysm
    public const DUNGEON_BLACKROCK_CAVERNS        = 'blackrock_caverns';
    public const DUNGEON_DEADMINES_CATACLYSM      = 'deadmines_cataclysm';
    public const DUNGEON_END_TIME                 = 'end_time';
    public const DUNGEON_GRIM_BATOL               = 'grim_batol';
    public const DUNGEON_HALLS_OF_ORIGINATION     = 'halls_of_origination';
    public const DUNGEON_HOUR_OF_TWILIGHT         = 'hour_of_twilight';
    public const DUNGEON_LOST_CITY_OF_THE_TOL_VIR = 'lost_city_of_the_tol_vir';
    public const DUNGEON_SHADOWFANG_KEEP_CATA     = 'shadowfang_keep_cataclysm';
    public const DUNGEON_THE_STONECORE            = 'the_stonecore';
    public const DUNGEON_THE_VORTEX_PINNACLE      = 'skywall';
    public const DUNGEON_THRONE_OF_THE_TIDES      = 'throne_of_the_tides'; // throneoftides
    public const DUNGEON_WELL_OF_ETERNITY         = 'well_of_eternity';
    public const DUNGEON_ZUL_AMAN                 = 'zul_aman';
    public const DUNGEON_ZUL_GURUB                = 'zul_gurub';

    // Mists of Pandaria
    public const DUNGEON_GATE_OF_THE_SETTING_SUN    = 'gate_of_the_setting_sun';
    public const DUNGEON_MOGU_SHAN_PALACE           = 'mogu_shan palace';
    public const DUNGEON_SCARLET_HALLS_MOP          = 'scarlet_halls_mop';
    public const DUNGEON_SCARLET_MONASTERY_MOP      = 'scarlet_monastery_mop';
    public const DUNGEON_SCHOLOMANCE_MOP            = 'scholomance_mop';
    public const DUNGEON_SHADO_PAN_MONASTERY        = 'shado_pan_monastery';
    public const DUNGEON_SIEGE_OF_NIUZAO_TEMPLE     = 'siege_of_niu_zao_temple';
    public const DUNGEON_STORMSTOUT_BREWERY         = 'stormstout_brewery';
    public const DUNGEON_TEMPLE_OF_THE_JADE_SERPENT = 'templeofthejadeserpent';

    // Warlords of Draenor
    public const DUNGEON_AUCHINDOUN                = 'auchindoun';
    public const DUNGEON_BLOODMAUL_SLAG_MINES      = 'bloodmaulslagmines';
    public const DUNGEON_IRON_DOCKS                = 'irondocks';
    public const DUNGEON_GRIMRAIL_DEPOT            = 'grimraildepot';
    public const DUNGEON_SHADOWMOON_BURIAL_GROUNDS = 'shadowmoonburialgrounds';
    public const DUNGEON_SKYREACH                  = 'skyreach';
    public const DUNGEON_THE_EVERBLOOM             = 'theeverbloom'; // overgrownoutput

    // Legion
    public const DUNGEON_ARCWAY                      = 'arcway';
    public const DUNGEON_BLACK_ROOK_HOLD             = 'blackrookhold';
    public const DUNGEON_CATHEDRAL_OF_ETERNAL_NIGHT  = 'cathedralofeternalnight';
    public const DUNGEON_COURT_OF_STARS              = 'courtofstars';
    public const DUNGEON_DARKHEART_THICKET           = 'darkheartthicket';
    public const DUNGEON_EYE_OF_AZSHARA              = 'eyeofazshara';
    public const DUNGEON_HALLS_OF_VALOR              = 'hallsofvalor';
    public const DUNGEON_LOWER_KARAZHAN              = 'lowerkarazhan';
    public const DUNGEON_MAW_OF_SOULS                = 'mawofsouls';
    public const DUNGEON_NELTHARIONS_LAIR            = 'neltharionslair';
    public const DUNGEON_UPPER_KARAZHAN              = 'upperkarazhan';
    public const DUNGEON_THE_SEAT_OF_THE_TRIUMVIRATE = 'theseatofthetriumvirate';
    public const DUNGEON_VAULT_OF_THE_WARDENS        = 'vaultofthewardens';

    // Battle for Azeroth
    public const DUNGEON_ATAL_DAZAR           = 'ataldazar';
    public const DUNGEON_FREEHOLD             = 'freehold';
    public const DUNGEON_KINGS_REST           = 'kingsrest';
    public const DUNGEON_SHRINE_OF_THE_STORM  = 'shrineofthestorm';
    public const DUNGEON_SIEGE_OF_BORALUS     = 'siegeofboralus';
    public const DUNGEON_TEMPLE_OF_SETHRALISS = 'templeofsethraliss';
    public const DUNGEON_THE_MOTHERLODE       = 'themotherlode';
    public const DUNGEON_THE_UNDERROT         = 'theunderrot';
    public const DUNGEON_TOL_DAGOR            = 'toldagor';
    public const DUNGEON_WAYCREST_MANOR       = 'waycrestmanor';
    public const DUNGEON_MECHAGON_JUNKYARD    = 'mechagonjunkyard';
    public const DUNGEON_MECHAGON_WORKSHOP    = 'mechagonworkshop';

    // Shadowlands
    public const DUNGEON_DE_OTHER_SIDE              = 'deotherside_ardenweald';
    public const DUNGEON_HALLS_OF_ATONEMENT         = 'hallsofatonement_a';
    public const DUNGEON_MISTS_OF_TIRNA_SCITHE      = 'mistsoftirnescithe';
    public const DUNGEON_PLAGUEFALL                 = 'plaguefall';
    public const DUNGEON_SANGUINE_DEPTHS            = 'sanguinedepths_a';
    public const DUNGEON_SPIRES_OF_ASCENSION        = 'spiresofascension_a';
    public const DUNGEON_THE_NECROTIC_WAKE          = 'necroticwake_a';
    public const DUNGEON_THEATER_OF_PAIN            = 'theaterofpain';
    public const DUNGEON_TAZAVESH_STREETS_OF_WONDER = 'tazaveshstreetsofwonder';
    public const DUNGEON_TAZAVESH_SO_LEAHS_GAMBIT   = 'tazaveshsoleahsgambit';

    // Dragonflight
    public const DUNGEON_ALGETH_AR_ACADEMY                    = 'dragonacademy';
    public const DUNGEON_BRACKENHIDE_HOLLOW                   = 'brackenhide';
    public const DUNGEON_HALLS_OF_INFUSION                    = 'hallsofinfusion';
    public const DUNGEON_NELTHARUS                            = 'neltharus';
    public const DUNGEON_RUBY_LIFE_POOLS                      = 'rubylifepools';
    public const DUNGEON_THE_AZURE_VAULT                      = 'theazurevault';
    public const DUNGEON_THE_NOKHUD_OFFENSIVE                 = 'nokhudoffensive';
    public const DUNGEON_ULDAMAN_LEGACY_OF_TYR                = 'uldamanlegacyoftyr';
    public const DUNGEON_DAWN_OF_THE_INFINITE_GALAKRONDS_FALL = 'dawn_of_the_infinite_galakronds_fall';
    public const DUNGEON_DAWN_OF_THE_INFINITE_MUROZONDS_RISE  = 'dawn_of_the_infinite_murozonds_rise';

    // The War Within
    public const DUNGEON_ARA_KARA_CITY_OF_ECHOES    = 'ara_karacityofechoes';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     //cityofechoes
    public const DUNGEON_CINDERBREW_MEADERY         = 'cinderbrewmeadery';
    public const DUNGEON_CITY_OF_THREADS            = 'cityofthreads'; // cityofthreadsdungeon
    public const DUNGEON_DARKFLAME_CLEFT            = 'darkflamecleft';
    public const DUNGEON_PRIORY_OF_THE_SACRED_FLAME = 'prioryofthesacredflame'; // sacredflame
    public const DUNGEON_THE_DAWNBREAKER            = 'thedawnbreaker';         // dawnbreaker
    public const DUNGEON_THE_ROOKERY                = 'therookery';             // rookerydungeon
    public const DUNGEON_THE_STONEVAULT             = 'thestonevault';          // stonevault_foundry

    public const ALL = [
        Expansion::EXPANSION_CLASSIC      => [
            self::DUNGEON_BLACKFATHOM_DEEPS,
            self::DUNGEON_BLACKROCK_DEPTHS,
            self::DUNGEON_DEADMINES,
            self::DUNGEON_DIRE_MAUL_WEST,
            self::DUNGEON_DIRE_MAUL_NORTH,
            self::DUNGEON_DIRE_MAUL_EAST,
            self::DUNGEON_GNOMEREGAN,
            self::DUNGEON_GNOMEREGAN_SOD,
            self::DUNGEON_LOWER_BLACKROCK_SPIRE,
            self::DUNGEON_MARAUDON,
            self::DUNGEON_RAGEFIRE_CHASM,
            self::DUNGEON_RAZORFEN_DOWNS,
            self::DUNGEON_RAZORFEN_KRAUL,
            self::DUNGEON_SCARLET_MONASTERY_ARMORY,
            self::DUNGEON_SCARLET_MONASTERY_CATHEDRAL,
            self::DUNGEON_SCARLET_MONASTERY_GRAVEYARD,
            self::DUNGEON_SCARLET_MONASTERY_LIBRARY,
            self::DUNGEON_SCHOLOMANCE,
            self::DUNGEON_SHADOWFANG_KEEP,
            self::DUNGEON_STRATHOLME,
            self::DUNGEON_THE_STOCKADE,
            self::DUNGEON_THE_TEMPLE_OF_ATAL_HAKKAR,
            self::DUNGEON_ULDAMAN,
            self::DUNGEON_UPPER_BLACKROCK_SPIRE,
            self::DUNGEON_WAILING_CAVERNS,
            self::DUNGEON_ZUL_FARRAK,
        ],
        Expansion::EXPANSION_TBC          => [
            self::DUNGEON_ACHENAI_CRYPTS,
            self::DUNGEON_HELLFIRE_RAMPARTS,
            self::DUNGEON_MAGISTERS_TERRACE,
            self::DUNGEON_MANA_TOMBS,
            self::DUNGEON_OLD_HILLSBRAD_FOOTHILLS,
            self::DUNGEON_SETHEKK_HALLS,
            self::DUNGEON_SHADOW_LABYRINTH,
            self::DUNGEON_THE_ARCATRAZ,
            self::DUNGEON_THE_BLACK_MORASS,
            self::DUNGEON_THE_BLOOD_FURNACE,
            self::DUNGEON_THE_BOTANICA,
            self::DUNGEON_THE_MECHANAR,
            self::DUNGEON_THE_SHATTERED_HALLS,
            self::DUNGEON_THE_SLAVE_PENS,
            self::DUNGEON_THE_STEAMVAULT,
            self::DUNGEON_THE_UNDERBOG,
        ],
        Expansion::EXPANSION_WOTLK        => [
            self::DUNGEON_AHN_KAHET_THE_OLD_KINGDOM,
            self::DUNGEON_AZJOL_NERUB,
            self::DUNGEON_DRAK_THARON_KEEP,
            self::DUNGEON_GUNDRAK,
            self::DUNGEON_HALLS_OF_LIGHTNING,
            self::DUNGEON_HALLS_OF_REFLECTION,
            self::DUNGEON_HALLS_OF_STONE,
            self::DUNGEON_PIT_OF_SARON,
            self::DUNGEON_THE_CULLING_OF_STRATHOLME,
            self::DUNGEON_THE_FORGE_OF_SOULS,
            self::DUNGEON_THE_NEXUS,
            self::DUNGEON_THE_OCULUS,
            self::DUNGEON_THE_VIOLET_HOLD,
            self::DUNGEON_TRIAL_OF_THE_CHAMPION,
            self::DUNGEON_UTGARDE_KEEP,
            self::DUNGEON_UTGARDE_PINNACLE,
        ],
        Expansion::EXPANSION_CATACLYSM    => [
            self::DUNGEON_BLACKROCK_CAVERNS,
            self::DUNGEON_DEADMINES_CATACLYSM,
            self::DUNGEON_END_TIME,
            self::DUNGEON_GRIM_BATOL,
            self::DUNGEON_HALLS_OF_ORIGINATION,
            self::DUNGEON_HOUR_OF_TWILIGHT,
            self::DUNGEON_LOST_CITY_OF_THE_TOL_VIR,
            self::DUNGEON_SHADOWFANG_KEEP_CATA,
            self::DUNGEON_THE_STONECORE,
            self::DUNGEON_THE_VORTEX_PINNACLE,
            self::DUNGEON_THRONE_OF_THE_TIDES,
            self::DUNGEON_WELL_OF_ETERNITY,
            self::DUNGEON_ZUL_AMAN,
            self::DUNGEON_ZUL_GURUB,
        ],
        Expansion::EXPANSION_MOP          => [
            self::DUNGEON_GATE_OF_THE_SETTING_SUN,
            self::DUNGEON_MOGU_SHAN_PALACE,
            self::DUNGEON_SCARLET_HALLS_MOP,
            self::DUNGEON_SCARLET_MONASTERY_MOP,
            self::DUNGEON_SCHOLOMANCE_MOP,
            self::DUNGEON_SHADO_PAN_MONASTERY,
            self::DUNGEON_SIEGE_OF_NIUZAO_TEMPLE,
            self::DUNGEON_STORMSTOUT_BREWERY,
            self::DUNGEON_TEMPLE_OF_THE_JADE_SERPENT,
        ],
        Expansion::EXPANSION_WOD          => [
            self::DUNGEON_AUCHINDOUN,
            self::DUNGEON_BLOODMAUL_SLAG_MINES,
            self::DUNGEON_IRON_DOCKS,
            self::DUNGEON_GRIMRAIL_DEPOT,
            self::DUNGEON_SHADOWMOON_BURIAL_GROUNDS,
            self::DUNGEON_SKYREACH,
            self::DUNGEON_THE_EVERBLOOM,
        ],
        Expansion::EXPANSION_LEGION       => [
            self::DUNGEON_ARCWAY,
            self::DUNGEON_BLACK_ROOK_HOLD,
            self::DUNGEON_CATHEDRAL_OF_ETERNAL_NIGHT,
            self::DUNGEON_COURT_OF_STARS,
            self::DUNGEON_DARKHEART_THICKET,
            self::DUNGEON_EYE_OF_AZSHARA,
            self::DUNGEON_HALLS_OF_VALOR,
            self::DUNGEON_LOWER_KARAZHAN,
            self::DUNGEON_MAW_OF_SOULS,
            self::DUNGEON_NELTHARIONS_LAIR,
            self::DUNGEON_UPPER_KARAZHAN,
            self::DUNGEON_THE_SEAT_OF_THE_TRIUMVIRATE,
            self::DUNGEON_VAULT_OF_THE_WARDENS,
        ],
        Expansion::EXPANSION_BFA          => [
            self::DUNGEON_ATAL_DAZAR,
            self::DUNGEON_FREEHOLD,
            self::DUNGEON_KINGS_REST,
            self::DUNGEON_SHRINE_OF_THE_STORM,
            self::DUNGEON_SIEGE_OF_BORALUS,
            self::DUNGEON_TEMPLE_OF_SETHRALISS,
            self::DUNGEON_THE_MOTHERLODE,
            self::DUNGEON_THE_UNDERROT,
            self::DUNGEON_TOL_DAGOR,
            self::DUNGEON_WAYCREST_MANOR,
            self::DUNGEON_MECHAGON_JUNKYARD,
            self::DUNGEON_MECHAGON_WORKSHOP,
        ],
        Expansion::EXPANSION_SHADOWLANDS  => [
            self::DUNGEON_DE_OTHER_SIDE,
            self::DUNGEON_HALLS_OF_ATONEMENT,
            self::DUNGEON_MISTS_OF_TIRNA_SCITHE,
            self::DUNGEON_PLAGUEFALL,
            self::DUNGEON_SANGUINE_DEPTHS,
            self::DUNGEON_SPIRES_OF_ASCENSION,
            self::DUNGEON_THE_NECROTIC_WAKE,
            self::DUNGEON_THEATER_OF_PAIN,
            self::DUNGEON_TAZAVESH_STREETS_OF_WONDER,
            self::DUNGEON_TAZAVESH_SO_LEAHS_GAMBIT,
        ],
        Expansion::EXPANSION_DRAGONFLIGHT => [
            self::DUNGEON_BRACKENHIDE_HOLLOW,
            self::DUNGEON_HALLS_OF_INFUSION,
            self::DUNGEON_NELTHARUS,
            self::DUNGEON_RUBY_LIFE_POOLS,
            self::DUNGEON_ALGETH_AR_ACADEMY,
            self::DUNGEON_THE_AZURE_VAULT,
            self::DUNGEON_THE_NOKHUD_OFFENSIVE,
            self::DUNGEON_ULDAMAN_LEGACY_OF_TYR,
            self::DUNGEON_DAWN_OF_THE_INFINITE_GALAKRONDS_FALL,
            self::DUNGEON_DAWN_OF_THE_INFINITE_MUROZONDS_RISE,
        ],
        Expansion::EXPANSION_TWW          => [
            self::DUNGEON_ARA_KARA_CITY_OF_ECHOES,
            self::DUNGEON_CINDERBREW_MEADERY,
            self::DUNGEON_CITY_OF_THREADS,
            self::DUNGEON_DARKFLAME_CLEFT,
            self::DUNGEON_PRIORY_OF_THE_SACRED_FLAME,
            self::DUNGEON_THE_DAWNBREAKER,
            self::DUNGEON_THE_ROOKERY,
            self::DUNGEON_THE_STONEVAULT,
        ],
    ];

    public const ALL_RAID = [
        Expansion::EXPANSION_WOTLK => [
            self::RAID_ICECROWN_CITADEL,
            self::RAID_NAXXRAMAS,
            self::RAID_ONYXIAS_LAIR,
            self::RAID_CRUSADERS_COLISEUM_TRIAL_OF_THE_CRUSADER,
            self::RAID_THE_EYE_OF_ETERNITY,
            self::RAID_THE_OBSIDIAN_SANCTUM,
            self::RAID_THE_RUBY_SANCTUM,
            self::RAID_ULDUAR,
            self::RAID_VAULT_OF_ARCHAVON,
        ],
    ];

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return int The amount of floors this dungeon has.
     */
    public function getFloorCountAttribute(): int
    {
        return $this->floors->count();
    }

    public function getMdtSupportedAttribute(): bool
    {
        return Conversion::hasMDTDungeonName($this->key);
    }

    /**
     * Gets the amount of enemy forces that this dungeon has mapped (non-zero enemy_forces on NPCs)
     */
    public function getEnemyForcesMappedStatusAttribute(): array
    {
        $result = [];
        $npcs   = [];

        try {
            // Loop through all floors
            foreach ($this->npcs as $npc) {
                /** @var $npc Npc */
                if ($npc !== null && $npc->classification_id < NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]) {
                    /** @var NpcEnemyForces|null $npcEnemyForces */
                    $npcEnemyForces = $npc->enemyForcesByMappingVersion()->first();

                    $npcs[$npc->id] = ($npcEnemyForces?->enemy_forces ?? -1) >= 0;
                }
            }
        } catch (Exception $exception) {
            dd($exception);
        }

        // Calculate which ones are unmapped
        $unmappedCount = 0;
        foreach ($npcs as $id => $npc) {
            if (!$npc) {
                $unmappedCount++;
            }
        }

        $total              = count($npcs);
        $result['npcs']     = $npcs;
        $result['unmapped'] = $unmappedCount;
        $result['total']    = $total;
        $result['percent']  = $total <= 0 ? 0 : 100 - (($unmappedCount / $total) * 100);

        return $result;
    }

    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    public function mappingVersions(): HasMany
    {
        return $this->hasMany(MappingVersion::class)->orderByDesc('mapping_versions.version');
    }

    public function currentMappingVersion(): HasOne
    {
        return $this->hasOne(MappingVersion::class)
            ->without(['dungeon'])
            ->orderByDesc('mapping_versions.version')
            ->limit(1);
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class)->orderBy('index');
    }

    public function activeFloors(): HasMany
    {
        return $this->floors()->active();
    }

    public function floorsForMapFacade(MappingVersion $mappingVersion, ?bool $useFacade = null): HasMany
    {
        $useFacade = $useFacade ?? $mappingVersion->facade_enabled;

        // If we use facade
        // If we have facade, only return facade floor
        // Otherwise, return all non-facade floors

        return $this->hasMany(Floor::class)
            ->where(static function (Builder $builder) use ($mappingVersion, $useFacade) {
                $builder->when(!$useFacade, static function (Builder $builder) {
                    $builder->where('facade', 0);
                })->when($useFacade, static function (Builder $builder) use ($mappingVersion, $useFacade) {
                    if ($mappingVersion->facade_enabled) {
                        $builder->where('facade', $useFacade);
                    } else {
                        $builder->where('facade', 0);
                    }
                });
            })
            ->orderBy('index');
    }

    public function dungeonRoutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }

    public function dungeonRoutesForExport(): HasMany
    {
        return $this->dungeonRoutes()->where('demo', true);
    }

    public function npcs(bool $includeGlobalNpcs = true): HasMany
    {
        return $this->hasMany(Npc::class)
            ->when($includeGlobalNpcs, static function (Builder $builder) {
                $builder->orWhere('dungeon_id', -1);
            });
    }

    public function enemies(): HasManyThrough
    {
        return $this->hasManyThrough(Enemy::class, Floor::class);
    }

    public function enemyPacks(): HasManyThrough
    {
        return $this->hasManyThrough(EnemyPack::class, Floor::class);
    }

    public function enemyPatrols(): HasManyThrough
    {
        return $this->hasManyThrough(EnemyPatrol::class, Floor::class);
    }

    public function mapIcons(): HasManyThrough
    {
        return $this->hasManyThrough(MapIcon::class, Floor::class)
            ->where(static fn(Builder $builder) => $builder
                ->whereNull('dungeon_route_id'));
    }

    public function dungeonFloorSwitchMarkers(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonFloorSwitchMarker::class, Floor::class);
    }

    public function mountableAreas(): HasManyThrough
    {
        return $this->hasManyThrough(MountableArea::class, Floor::class);
    }

    public function dungeonSpeedrunRequiredNpcs10Man(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonSpeedrunRequiredNpc::class, Floor::class)
            ->where('difficulty', Dungeon::DIFFICULTY_10_MAN);
    }

    public function dungeonSpeedrunRequiredNpcs25Man(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonSpeedrunRequiredNpc::class, Floor::class)
            ->where('difficulty', Dungeon::DIFFICULTY_25_MAN);
    }

    /**
     * Scope a query to only the Siege of Boralus dungeon.
     */
    public function scopeFactionSelectionRequired(Builder $query): Builder
    {
        return $query->whereIn('key', [self::DUNGEON_SIEGE_OF_BORALUS, self::DUNGEON_THE_NEXUS]);
    }

    /**
     * Scope a query to only include active dungeons.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('dungeons.active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('dungeons.active', 0);
    }

    public function getDungeonStart(): ?MapIcon
    {
        $result = null;

        foreach ($this->floors as $floor) {
            foreach ($floor->mapIcons as $mapicon) {
                if ($mapicon->map_icon_type_id === MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START]) {
                    $result = $mapicon;
                    break;
                }
            }
        }

        return $result;
    }

    public function getFacadeFloor(): ?Floor
    {
        return $this->floors->first(static fn(Floor $floor) => $floor->facade);
    }

    /**
     * Get the season that is active for this dungeon right now (preferring upcoming seasons if current and next season overlap)
     */
    public function getActiveSeason(SeasonServiceInterface $seasonService): ?Season
    {
        $nextSeason = $seasonService->getNextSeasonOfExpansion();
        if ($nextSeason !== null && $nextSeason->hasDungeon($this)) {
            return $nextSeason;
        }

        // $currentSeason cannot be null - there's always a season for the current expansion
        $currentSeason = $seasonService->getCurrentSeason();
        if ($currentSeason->hasDungeon($this)) {
            return $currentSeason;
        }

        // Timewalking fallback
        return $seasonService->getCurrentSeason($this->expansion);
    }

    private function getNpcsHealthBuilderEnemyForcesDungeonExclusionList(): array
    {
        // Unpack all raids in a single array, see https://stackoverflow.com/a/46861938/771270
        $allRaids = array_merge(...array_values(self::ALL_RAID));

        return array_merge(
            $allRaids,
            // These expansions never had M+ so ignore exclusions based on enemy forces since they never had any
            self::ALL[Expansion::EXPANSION_CLASSIC],
            self::ALL[Expansion::EXPANSION_TBC],
            self::ALL[Expansion::EXPANSION_WOTLK],
            self::ALL[Expansion::EXPANSION_CATACLYSM],
            self::ALL[Expansion::EXPANSION_MOP],
            self::ALL[Expansion::EXPANSION_WOD],
        );
    }

    private function getNpcsHealthBuilder(MappingVersion $mappingVersion): HasMany
    {
        return $this->npcs(false)
            // Ensure that there's at least one enemy by having this join
            ->join('enemies', 'enemies.npc_id', 'npcs.id')
            ->where('enemies.mapping_version_id', $mappingVersion->id)
            ->where('classification_id', '<', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS])
            ->where('npc_type_id', '!=', NpcType::CRITTER)
            ->whereIn('aggressiveness', [Npc::AGGRESSIVENESS_AGGRESSIVE, Npc::AGGRESSIVENESS_UNFRIENDLY, Npc::AGGRESSIVENESS_AWAKENED])
            ->when(!in_array($this->key, $this->getNpcsHealthBuilderEnemyForcesDungeonExclusionList()),
                static fn(Builder $builder) => $builder
                    ->join('npc_enemy_forces', 'npc_enemy_forces.npc_id', 'npcs.id')
                    ->where('npc_enemy_forces.mapping_version_id', $mappingVersion->id))
            ->groupBy('enemies.npc_id');
    }

    /**
     * Get the minimum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMinHealth(MappingVersion $mappingVersion): int
    {
        return $this->getNpcsHealthBuilder($mappingVersion)->orderBy('npcs.base_health')->min('base_health') ?? 10000;
    }

    /**
     * Get the maximum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMaxHealth(MappingVersion $mappingVersion): int
    {
        return $this->getNpcsHealthBuilder($mappingVersion)->orderByDesc('npcs.base_health')->max('base_health') ?? 10000;
    }

    /**
     * @return Collection|Npc[]
     */
    public function getInUseNpcs(): Collection
    {
        return Npc::select('npcs.*')
            ->leftJoin('npc_enemy_forces', 'npcs.id', 'npc_enemy_forces.npc_id')
            ->where(fn(Builder $builder) => $builder->where('npcs.dungeon_id', $this->id)
                ->orWhere('npcs.dungeon_id', -1))
            ->where(function (Builder $builder) {
                $builder->where(function (Builder $builder) {
                    // Enemy forces may be not set, that means that we assume 0. They MAY be missing entirely for bosses
                    // or for other exceptions listed below
                    $builder->where('npc_enemy_forces.mapping_version_id', $this->currentMappingVersion->id)
                        ->whereNotNull('npc_enemy_forces.enemy_forces');
                })->orWhereIn('npcs.classification_id', [
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_RARE],
                ])->orWhereIn('npcs.id', [
                    // Neltharion's Lair:
                    // Burning Geodes are in the mapping but give 0 enemy forces.
                    // They're in the mapping because they're dangerous af
                    101437,
                    // Halls of Infusion:
                    // Aqua Ragers are in the mapping but give 0 enemy forces - so would be excluded.
                    // They're in the mapping because they are a significant drain on time and excluding them would raise questions about why they're gone
                    190407,
                    // Brackenhide Hollow:
                    // Witherlings that are a significant nuisance to be included in the mapping. They give 0 enemy forces.
                    194273,
                    // Rotfang Hyena are part of Gutshot boss but, they are part of the mapping. They give 0 enemy forces.
                    194745,
                    // Wild Lashers give 0 enemy forces but are in the mapping regardless
                    191243,
                    // Wither Slashers give 0 enemy forces but are in the mapping regardless
                    194469,
                    // Gutstabbers give 0 enemy forces but are in the mapping regardless
                    197857,
                    // Nokhud Offensive:
                    // War Ohuna gives 0 enemy forces but is in the mapping regardless
                    192803,
                    // Stormsurge Totem gives 0 enemy forces but is in the mapping regardless
                    194897,
                    // Unstable Squall gives 0 enemy forces but is in the mapping regardless
                    194895,
                    // Primal Gust gives 0 enemy forces but is in the mapping regardless
                    195579,
                    // Dawn of the Infinite:
                    // Temporal Deviation gives 0 enemy forces but is in the mapping regardless
                    206063,
                    // Iridikron's Creation
                    204918,
                ]);
            })
            ->get();
    }

    /**
     * @return Collection<int>
     */
    public function getInUseNpcIds(): Collection
    {
        return $this->getInUseNpcs()
            ->pluck('id')
            // Brackenhide Hollow:  Odd exception to make Brackenhide Gnolls show up. They aren't in the MDT mapping, so
            // they don't get npc_enemy_forces pushed. But we do need them to show up for us since they convert
            // into Witherlings which ARE on the mapping. Without this exception, they wouldn't turn up and the
            // Witherlings would never get mapped properly
            ->push(194373);
    }

    public function isFactionSelectionRequired(): bool
    {
        return in_array($this->key, [self::DUNGEON_SIEGE_OF_BORALUS, self::DUNGEON_THE_NEXUS]);
    }

    /**
     * Checks if this dungeon is Siege of Boralus. It's a bit of a special dungeon because of horde/alliance differences,
     * hence this function, so we can use it to differentiate between the two.
     */
    public function isSiegeOfBoralus(): bool
    {
        return $this->key === self::DUNGEON_SIEGE_OF_BORALUS;
    }

    /**
     * Checks if this dungeon is Tol Dagor. It's a bit of a special dungeon because of a shitty MDT bug.
     */
    public function isTolDagor(): bool
    {
        return $this->key === self::DUNGEON_TOL_DAGOR;
    }

    public function getTimerUpgradePlusTwoSeconds(): int
    {
        return $this->timer_max_seconds * config('keystoneguru.keystone.timer.plustwofactor');
    }

    public function getTimerUpgradePlusThreeSeconds(): int
    {
        return $this->timer_max_seconds * config('keystoneguru.keystone.timer.plusthreefactor');
    }

    public function getImageUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s.jpg', $this->expansion->shortname, $this->key));
    }

    public function getImage32Url(): string
    {
        return url(sprintf('images/dungeons/%s/%s_3-2.jpg', $this->expansion->shortname, $this->key));
    }

    public function getImageTransparentUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s_transparent.png', $this->expansion->shortname, $this->key));
    }

    public function getImageWallpaperUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s_wallpaper.jpg', $this->expansion->shortname, $this->key));
    }

    public function hasImageWallpaper(): bool
    {
        return file_exists(resource_path(sprintf('assets/images/dungeons/%s/%s_wallpaper.jpg', $this->expansion->shortname, $this->key)));
    }

    public static function findExpansionByKey(string $key): ?string
    {
        $result = null;

        foreach (self::ALL as $expansion => $dungeonKeys) {
            if (in_array($key, $dungeonKeys)) {
                $result = $expansion;
                break;
            }
        }

        return $result;
    }

    public function getDungeonId(): ?int
    {
        return $this->id;
    }

    public static function boot(): void
    {
        parent::boot();

        static::deleting(static fn(Model $model) => false);
    }
}
