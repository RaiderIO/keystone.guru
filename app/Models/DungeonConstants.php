<?php

namespace App\Models;

trait DungeonConstants
{
    public const DIFFICULTY_10_MAN = 1;

    public const DIFFICULTY_25_MAN = 2;
    public const DIFFICULTY_20_MAN = 3;

    public const DIFFICULTY_40_MAN = 4;

    public const DIFFICULTY_ALL = [
        self::DIFFICULTY_10_MAN,
        self::DIFFICULTY_25_MAN,
        self::DIFFICULTY_20_MAN,
        self::DIFFICULTY_40_MAN,
    ];

    // @formatter:off
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
    public const DUNGEON_SCHOLOMANCE                 = 'scholomance'; //scholomanceold
    public const DUNGEON_SHADOWFANG_KEEP             = 'shadowfang_keep'; //shadowfangkeep
    public const DUNGEON_STRATHOLME                  = 'stratholme';
    public const DUNGEON_THE_STOCKADE                = 'the_stockade';              //thestockade
    public const DUNGEON_THE_TEMPLE_OF_ATAL_HAKKAR   = 'the_temple_of_atal_hakkar'; //thetempleofatalhakkar
    public const DUNGEON_ULDAMAN                     = 'uldaman';
    public const DUNGEON_UPPER_BLACKROCK_SPIRE       = 'upper_blackrock_spire'; //upperblackrockspire
    public const DUNGEON_WAILING_CAVERNS             = 'wailing_caverns';       //wailingcaverns
    public const DUNGEON_ZUL_FARRAK                  = 'zul_farrak';            //zulfarrak

    // Classic: Season of Discovery
    public const RAID_GNOMEREGAN_SOD          = 'gnomeregan_sod'; //gnomeregan
    public const RAID_RUINS_OF_AHN_QIRAJ_SOD  = 'ruins_of_ahnqiraj_sod';  // 20-man (classic)
    public const RAID_TEMPLE_OF_AHN_QIRAJ_SOD = 'temple_of_ahnqiraj_sod'; // 40-man (classic)
    public const DUNGEON_KARAZHAN_CRYPTS      = 'karazhan_crypts';

    public const RAID_ZUL_GURUB           = 'zulgurub';
    public const RAID_ONYXIAS_LAIR        = 'onyxias_lair_classic';
    public const RAID_MOLTEN_CORE         = 'moltencore';
    public const RAID_BLACKWING_LAIR      = 'blackwinglair';
    public const RAID_RUINS_OF_AHN_QIRAJ  = 'ruins_of_ahnqiraj_classic';  // 20-man (classic)
    public const RAID_TEMPLE_OF_AHN_QIRAJ = 'temple_of_ahnqiraj_classic'; // 40-man (classic)
    public const RAID_NAXXRAMAS           = 'naxxramas_classic';
    public const RAID_SCARLET_ENCLAVE     = 'scarlet_enclave';


    // The Burning Crusade
    public const DUNGEON_AUCHENAI_CRYPTS         = 'auchenai_crypts';
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
    public const RAID_NAXXRAMAS_WOTLK                          = 'naxxramas';
    public const RAID_ONYXIAS_LAIR_WOTLK                       = 'onyxias_lair';
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

    // Cataclysm raid
    public const RAID_FIRELANDS   = 'firelands';
    public const RAID_DRAGON_SOUL = 'dragonsoul';

    // Mists of Pandaria
    public const DUNGEON_GATE_OF_THE_SETTING_SUN    = 'gate_of_the_setting_sun'; // TheGreatWall
    public const DUNGEON_MOGU_SHAN_PALACE           = 'mogu_shan_palace';
    public const DUNGEON_SCARLET_HALLS_MOP          = 'scarlet_halls_mop';
    public const DUNGEON_SCARLET_MONASTERY_MOP      = 'scarlet_monastery_mop'; // scarletcathedral
    public const DUNGEON_SCHOLOMANCE_MOP            = 'scholomance_mop'; // scholomance
    public const DUNGEON_SHADO_PAN_MONASTERY        = 'shado_pan_monastery'; // shadowpanhideout
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
    public const DUNGEON_THE_EVERBLOOM             = 'theeverbloom'; // overgrownoutpost

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
    public const DUNGEON_ARA_KARA_CITY_OF_ECHOES    = 'ara_karacityofechoes'; // cityofechoes
    public const DUNGEON_CINDERBREW_MEADERY         = 'cinderbrewmeadery';
    public const DUNGEON_CITY_OF_THREADS            = 'cityofthreads'; // nerubardungeon
    public const DUNGEON_DARKFLAME_CLEFT            = 'darkflamecleft';
    public const DUNGEON_PRIORY_OF_THE_SACRED_FLAME = 'prioryofthesacredflame'; // sacredflame
    public const DUNGEON_THE_DAWNBREAKER            = 'thedawnbreaker'; // dawnbreaker (aka harrowfall)
    public const DUNGEON_THE_ROOKERY                = 'therookery'; // rookerydungeon
    public const DUNGEON_THE_STONEVAULT             = 'thestonevault'; // stonevault_foundry
    public const DUNGEON_OPERATION_FLOODGATE        = 'operationfloodgate';
    // @formatter:on

    public const ALL = [
        Expansion::EXPANSION_CLASSIC      => [
            self::DUNGEON_BLACKFATHOM_DEEPS,
            self::DUNGEON_BLACKROCK_DEPTHS,
            self::DUNGEON_DEADMINES,
            self::DUNGEON_DIRE_MAUL_WEST,
            self::DUNGEON_DIRE_MAUL_NORTH,
            self::DUNGEON_DIRE_MAUL_EAST,
            self::DUNGEON_GNOMEREGAN,
            self::DUNGEON_KARAZHAN_CRYPTS,
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
            self::DUNGEON_AUCHENAI_CRYPTS,
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
            self::DUNGEON_OPERATION_FLOODGATE,
        ],
    ];

    public const ALL_RAID = [
        Expansion::EXPANSION_CLASSIC   => [
            self::RAID_GNOMEREGAN_SOD,
            self::RAID_ZUL_GURUB,
            self::RAID_ONYXIAS_LAIR,
            self::RAID_MOLTEN_CORE,
            self::RAID_BLACKWING_LAIR,
            self::RAID_RUINS_OF_AHN_QIRAJ,
            self::RAID_TEMPLE_OF_AHN_QIRAJ,
            self::RAID_RUINS_OF_AHN_QIRAJ_SOD,
            self::RAID_TEMPLE_OF_AHN_QIRAJ_SOD,
            self::RAID_NAXXRAMAS,
            self::RAID_SCARLET_ENCLAVE,
        ],
        Expansion::EXPANSION_WOTLK     => [
            self::RAID_ICECROWN_CITADEL,
            self::RAID_NAXXRAMAS_WOTLK,
            self::RAID_ONYXIAS_LAIR_WOTLK,
            self::RAID_CRUSADERS_COLISEUM_TRIAL_OF_THE_CRUSADER,
            self::RAID_THE_EYE_OF_ETERNITY,
            self::RAID_THE_OBSIDIAN_SANCTUM,
            self::RAID_THE_RUBY_SANCTUM,
            self::RAID_ULDUAR,
            self::RAID_VAULT_OF_ARCHAVON,
        ],
        Expansion::EXPANSION_CATACLYSM => [
            self::RAID_FIRELANDS,
            self::RAID_DRAGON_SOUL,
        ],
    ];
}
