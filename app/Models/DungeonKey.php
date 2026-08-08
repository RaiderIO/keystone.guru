<?php

namespace App\Models;

/**
 * The `key` column of every dungeon known to the application. Previously `Dungeon::DUNGEON_*`.
 */
enum DungeonKey: string
{
    // @formatter:off
    // Classic
    case BLACKFATHOM_DEEPS           = 'blackfathom_deeps';           //blackfanthomdeeps
    case BLACKROCK_DEPTHS            = 'blackrock_depths';            //blackrockdepths
    case DEADMINES                   = 'deadmines';                   //thedeadmines
    case DIRE_MAUL_WEST              = 'dire_maul_west';              //diremaul
    case DIRE_MAUL_NORTH             = 'dire_maul_north';             //diremaul
    case DIRE_MAUL_EAST              = 'dire_maul_east';              //diremaul
    case GNOMEREGAN                  = 'gnomeregan';                  //gnomeregan
    case KARAZHAN_CRYPTS             = 'karazhan_crypts';
    case LOWER_BLACKROCK_SPIRE       = 'lower_blackrock_spire';       //blackrockspire
    case MARAUDON                    = 'maraudon';
    case RAGEFIRE_CHASM              = 'ragefire_chasm';              //ragefire
    case RAZORFEN_DOWNS              = 'razorfen_downs';              //razorfendowns
    case RAZORFEN_KRAUL              = 'razorfen_kraul';              //razorfenkraul
    case SCARLET_MONASTERY_ARMORY    = 'scarlet_monastery_armory';    //scarletmonastery
    case SCARLET_MONASTERY_CATHEDRAL = 'scarlet_monastery_cathedral'; //scarletmonastery
    case SCARLET_MONASTERY_GRAVEYARD = 'scarlet_monastery_graveyard'; //scarletmonastery
    case SCARLET_MONASTERY_LIBRARY   = 'scarlet_monastery_library';   //scarletmonastery
    case SCHOLOMANCE                 = 'scholomance';                 //scholomanceold
    case SHADOWFANG_KEEP             = 'shadowfang_keep';             //shadowfangkeep
    case STRATHOLME                  = 'stratholme';
    case THE_STOCKADE                = 'the_stockade';                //thestockade
    case THE_TEMPLE_OF_ATAL_HAKKAR   = 'the_temple_of_atal_hakkar';   //thetempleofatalhakkar
    case ULDAMAN                     = 'uldaman';
    case UPPER_BLACKROCK_SPIRE       = 'upper_blackrock_spire';       //upperblackrockspire
    case WAILING_CAVERNS             = 'wailing_caverns';             //wailingcaverns
    case ZUL_FARRAK                  = 'zul_farrak';                  //zulfarrak

    // The Burning Crusade
    case AUCHENAI_CRYPTS         = 'auchenai_crypts';
    case HELLFIRE_RAMPARTS       = 'hellfire_ramparts';
    case MAGISTERS_TERRACE       = 'magisters_terrace';
    case MANA_TOMBS              = 'mana_tombs';
    case OLD_HILLSBRAD_FOOTHILLS = 'old_hillsbrad_foothills';
    case SETHEKK_HALLS           = 'sethekk_halls';
    case SHADOW_LABYRINTH        = 'shadow_labyrinth';
    case THE_ARCATRAZ            = 'the_arcatraz';
    case THE_BLACK_MORASS        = 'the_black_morass';
    case THE_BLOOD_FURNACE       = 'the_blood_furnace';
    case THE_BOTANICA            = 'the_botanica';
    case THE_MECHANAR            = 'the_mechanar';
    case THE_SHATTERED_HALLS     = 'the_shattered_halls';
    case THE_SLAVE_PENS          = 'the_slave_pens';
    case THE_STEAMVAULT          = 'the_steamvault';
    case THE_UNDERBOG            = 'the_underbog';

    // Wrath of the Lich King
    case AHN_KAHET_THE_OLD_KINGDOM = 'ahnkahet';
    case AZJOL_NERUB               = 'azjolnerub';
    case DRAK_THARON_KEEP          = 'draktharonkeep';
    case GUNDRAK                   = 'gundrak';
    case HALLS_OF_LIGHTNING        = 'hallsoflightning';
    case HALLS_OF_REFLECTION       = 'hallsofreflection';
    case HALLS_OF_STONE            = 'hallsofstone';                // ulduar77
    case PIT_OF_SARON              = 'pitofsaron';
    case THE_CULLING_OF_STRATHOLME = 'thecullingofstratholme';      // cotstratholme
    case THE_FORGE_OF_SOULS        = 'theforgeofsouls';
    case THE_NEXUS                 = 'thenexus';
    case THE_OCULUS                = 'theoculus';                   // nexus80
    case THE_VIOLET_HOLD           = 'theviolethold';               // violethold
    case TRIAL_OF_THE_CHAMPION     = 'trialofthechampion';          // theargentcoliseum
    case UTGARDE_KEEP              = 'utgardekeep';
    case UTGARDE_PINNACLE          = 'utgardepinnacle';

    // cata
    case BLACKROCK_CAVERNS        = 'blackrock_caverns';
    case DEADMINES_CATACLYSM      = 'deadmines_cataclysm';
    case END_TIME                 = 'end_time';
    case GRIM_BATOL               = 'grim_batol';
    case HALLS_OF_ORIGINATION     = 'halls_of_origination';
    case HOUR_OF_TWILIGHT         = 'hour_of_twilight';
    case LOST_CITY_OF_THE_TOL_VIR = 'lost_city_of_the_tol_vir';
    case SHADOWFANG_KEEP_CATA     = 'shadowfang_keep_cataclysm';
    case THE_STONECORE            = 'the_stonecore';
    case THE_VORTEX_PINNACLE      = 'skywall';
    case THRONE_OF_THE_TIDES      = 'throne_of_the_tides';         // throneoftides
    case WELL_OF_ETERNITY         = 'well_of_eternity';
    case ZUL_AMAN                 = 'zul_aman';
    case ZUL_GURUB                = 'zul_gurub';

    // Mists of Pandaria
    case GATE_OF_THE_SETTING_SUN    = 'gate_of_the_setting_sun';     // TheGreatWall
    case MOGU_SHAN_PALACE           = 'mogu_shan_palace';
    case SCARLET_HALLS_MOP          = 'scarlet_halls_mop';
    case SCARLET_MONASTERY_MOP      = 'scarlet_monastery_mop';       // scarletcathedral
    case SCHOLOMANCE_MOP            = 'scholomance_mop';             // scholomance
    case SHADO_PAN_MONASTERY        = 'shado_pan_monastery';         // shadowpanhideout
    case SIEGE_OF_NIUZAO_TEMPLE     = 'siege_of_niu_zao_temple';
    case STORMSTOUT_BREWERY         = 'stormstout_brewery';
    case TEMPLE_OF_THE_JADE_SERPENT = 'templeofthejadeserpent';      // easttemple

    // Warlords of Draenor
    case AUCHINDOUN                = 'auchindoun';
    case BLOODMAUL_SLAG_MINES      = 'bloodmaul_slag_mines';
    case IRON_DOCKS                = 'irondocks';
    case GRIMRAIL_DEPOT            = 'grimraildepot';
    case SHADOWMOON_BURIAL_GROUNDS = 'shadowmoonburialgrounds';
    case SKYREACH                  = 'skyreach';                    // spiresofarakdungeon
    case THE_EVERBLOOM             = 'theeverbloom';                // overgrownoutpost

    // Legion
    case ARCWAY                      = 'arcway';
    case BLACK_ROOK_HOLD             = 'blackrookhold';
    case CATHEDRAL_OF_ETERNAL_NIGHT  = 'cathedralofeternalnight';
    case COURT_OF_STARS              = 'courtofstars';
    case DARKHEART_THICKET           = 'darkheartthicket';
    case EYE_OF_AZSHARA              = 'eyeofazshara';
    case HALLS_OF_VALOR              = 'hallsofvalor';
    case LOWER_KARAZHAN              = 'lowerkarazhan';
    case MAW_OF_SOULS                = 'mawofsouls';
    case NELTHARIONS_LAIR            = 'neltharionslair';
    case UPPER_KARAZHAN              = 'upperkarazhan';
    case THE_SEAT_OF_THE_TRIUMVIRATE = 'theseatofthetriumvirate';
    case VAULT_OF_THE_WARDENS        = 'vaultofthewardens';

    // Battle for Azeroth
    case ATAL_DAZAR           = 'ataldazar';
    case FREEHOLD             = 'freehold';
    case KINGS_REST           = 'kingsrest';
    case SHRINE_OF_THE_STORM  = 'shrineofthestorm';
    case SIEGE_OF_BORALUS     = 'siegeofboralus';
    case TEMPLE_OF_SETHRALISS = 'templeofsethraliss';
    case THE_MOTHERLODE       = 'themotherlode';
    case THE_UNDERROT         = 'theunderrot';
    case TOL_DAGOR            = 'toldagor';
    case WAYCREST_MANOR       = 'waycrestmanor';
    case MECHAGON_JUNKYARD    = 'mechagonjunkyard';
    case MECHAGON_WORKSHOP    = 'mechagonworkshop';

    // sl
    case DE_OTHER_SIDE              = 'deotherside_ardenweald';
    case HALLS_OF_ATONEMENT         = 'hallsofatonement_a';
    case MISTS_OF_TIRNA_SCITHE      = 'mistsoftirnescithe';
    case PLAGUEFALL                 = 'plaguefall';
    case SANGUINE_DEPTHS            = 'sanguinedepths_a';
    case SPIRES_OF_ASCENSION        = 'spiresofascension_a';
    case THE_NECROTIC_WAKE          = 'necroticwake_a';
    case THEATER_OF_PAIN            = 'theaterofpain';
    case TAZAVESH_STREETS_OF_WONDER = 'tazaveshstreetsofwonder';
    case TAZAVESH_SO_LEAHS_GAMBIT   = 'tazaveshsoleahsgambit';

    // df
    case BRACKENHIDE_HOLLOW                   = 'brackenhide';
    case HALLS_OF_INFUSION                    = 'hallsofinfusion';
    case NELTHARUS                            = 'neltharus';
    case RUBY_LIFE_POOLS                      = 'rubylifepools';
    case ALGETH_AR_ACADEMY                    = 'dragonacademy';
    case THE_AZURE_VAULT                      = 'theazurevault';
    case THE_NOKHUD_OFFENSIVE                 = 'nokhudoffensive';
    case ULDAMAN_LEGACY_OF_TYR                = 'uldamanlegacyoftyr';
    case DAWN_OF_THE_INFINITE_GALAKRONDS_FALL = 'dawn_of_the_infinite_galakronds_fall';
    case DAWN_OF_THE_INFINITE_MUROZONDS_RISE  = 'dawn_of_the_infinite_murozonds_rise';

    // The War Within
    case ARA_KARA_CITY_OF_ECHOES    = 'ara_karacityofechoes';        // cityofechoes
    case CINDERBREW_MEADERY         = 'cinderbrewmeadery';
    case CITY_OF_THREADS            = 'cityofthreads';               // nerubardungeon
    case DARKFLAME_CLEFT            = 'darkflamecleft';
    case PRIORY_OF_THE_SACRED_FLAME = 'prioryofthesacredflame';      // sacredflame
    case THE_DAWNBREAKER            = 'thedawnbreaker';              // dawnbreaker (aka harrowfall)
    case THE_ROOKERY                = 'therookery';                  // rookerydungeon
    case THE_STONEVAULT             = 'thestonevault';               // stonevault_foundry
    case OPERATION_FLOODGATE        = 'operationfloodgate';
    case ECO_DOME_AL_DANI           = 'ecodomealdani';               // ??

    // Midnight
    case DEN_OF_NALORAKK            = 'den_of_nalorakk';             // proveyourworth
    case MAGISTERS_TERRACE_MIDNIGHT = 'magisters_terrace_midnight';  // 12_magistersterrace
    case MAISARA_CAVERNS            = 'maisara_caverns';             // maisaracavernsdungeon
    case MURDER_ROW                 = 'murder_row';                  // murderrow
    case NEXUS_POINT_XENAS          = 'nexus_point_xenas';
    case THE_BLINDING_VALE          = 'the_blinding_vale';           // lightbloomdungeon
    case VOIDSCAR_ARENA             = 'voidscar_arena';              // ???
    case WINDRUNNER_SPIRE           = 'windrunner_spire';            // windrunnerspire
    case ALGETH_AR_ACADEMY_MIDNIGHT = 'algeth_ar_academy_midnight';  // dragonacademy
    case ALTAR_OF_FANGS             = 'altar_of_fangs';              // ulatek_dungeon
    // @formatter:on

    /**
     * The expansion key (one of the {@see Expansion} EXPANSION_* constants) this dungeon belongs to.
     */
    public function expansionKey(): string
    {
        return match ($this) {
            self::BLACKFATHOM_DEEPS,
            self::BLACKROCK_DEPTHS,
            self::DEADMINES,
            self::DIRE_MAUL_WEST,
            self::DIRE_MAUL_NORTH,
            self::DIRE_MAUL_EAST,
            self::GNOMEREGAN,
            self::KARAZHAN_CRYPTS,
            self::LOWER_BLACKROCK_SPIRE,
            self::MARAUDON,
            self::RAGEFIRE_CHASM,
            self::RAZORFEN_DOWNS,
            self::RAZORFEN_KRAUL,
            self::SCARLET_MONASTERY_ARMORY,
            self::SCARLET_MONASTERY_CATHEDRAL,
            self::SCARLET_MONASTERY_GRAVEYARD,
            self::SCARLET_MONASTERY_LIBRARY,
            self::SCHOLOMANCE,
            self::SHADOWFANG_KEEP,
            self::STRATHOLME,
            self::THE_STOCKADE,
            self::THE_TEMPLE_OF_ATAL_HAKKAR,
            self::ULDAMAN,
            self::UPPER_BLACKROCK_SPIRE,
            self::WAILING_CAVERNS,
            self::ZUL_FARRAK => Expansion::EXPANSION_CLASSIC,
            self::AUCHENAI_CRYPTS,
            self::HELLFIRE_RAMPARTS,
            self::MAGISTERS_TERRACE,
            self::MANA_TOMBS,
            self::OLD_HILLSBRAD_FOOTHILLS,
            self::SETHEKK_HALLS,
            self::SHADOW_LABYRINTH,
            self::THE_ARCATRAZ,
            self::THE_BLACK_MORASS,
            self::THE_BLOOD_FURNACE,
            self::THE_BOTANICA,
            self::THE_MECHANAR,
            self::THE_SHATTERED_HALLS,
            self::THE_SLAVE_PENS,
            self::THE_STEAMVAULT,
            self::THE_UNDERBOG => Expansion::EXPANSION_TBC,
            self::AHN_KAHET_THE_OLD_KINGDOM,
            self::AZJOL_NERUB,
            self::DRAK_THARON_KEEP,
            self::GUNDRAK,
            self::HALLS_OF_LIGHTNING,
            self::HALLS_OF_REFLECTION,
            self::HALLS_OF_STONE,
            self::PIT_OF_SARON,
            self::THE_CULLING_OF_STRATHOLME,
            self::THE_FORGE_OF_SOULS,
            self::THE_NEXUS,
            self::THE_OCULUS,
            self::THE_VIOLET_HOLD,
            self::TRIAL_OF_THE_CHAMPION,
            self::UTGARDE_KEEP,
            self::UTGARDE_PINNACLE => Expansion::EXPANSION_WOTLK,
            self::BLACKROCK_CAVERNS,
            self::DEADMINES_CATACLYSM,
            self::END_TIME,
            self::GRIM_BATOL,
            self::HALLS_OF_ORIGINATION,
            self::HOUR_OF_TWILIGHT,
            self::LOST_CITY_OF_THE_TOL_VIR,
            self::SHADOWFANG_KEEP_CATA,
            self::THE_STONECORE,
            self::THE_VORTEX_PINNACLE,
            self::THRONE_OF_THE_TIDES,
            self::WELL_OF_ETERNITY,
            self::ZUL_AMAN,
            self::ZUL_GURUB => Expansion::EXPANSION_CATACLYSM,
            self::GATE_OF_THE_SETTING_SUN,
            self::MOGU_SHAN_PALACE,
            self::SCARLET_HALLS_MOP,
            self::SCARLET_MONASTERY_MOP,
            self::SCHOLOMANCE_MOP,
            self::SHADO_PAN_MONASTERY,
            self::SIEGE_OF_NIUZAO_TEMPLE,
            self::STORMSTOUT_BREWERY,
            self::TEMPLE_OF_THE_JADE_SERPENT => Expansion::EXPANSION_MOP,
            self::AUCHINDOUN,
            self::BLOODMAUL_SLAG_MINES,
            self::IRON_DOCKS,
            self::GRIMRAIL_DEPOT,
            self::SHADOWMOON_BURIAL_GROUNDS,
            self::SKYREACH,
            self::THE_EVERBLOOM => Expansion::EXPANSION_WOD,
            self::ARCWAY,
            self::BLACK_ROOK_HOLD,
            self::CATHEDRAL_OF_ETERNAL_NIGHT,
            self::COURT_OF_STARS,
            self::DARKHEART_THICKET,
            self::EYE_OF_AZSHARA,
            self::HALLS_OF_VALOR,
            self::LOWER_KARAZHAN,
            self::MAW_OF_SOULS,
            self::NELTHARIONS_LAIR,
            self::UPPER_KARAZHAN,
            self::THE_SEAT_OF_THE_TRIUMVIRATE,
            self::VAULT_OF_THE_WARDENS => Expansion::EXPANSION_LEGION,
            self::ATAL_DAZAR,
            self::FREEHOLD,
            self::KINGS_REST,
            self::SHRINE_OF_THE_STORM,
            self::SIEGE_OF_BORALUS,
            self::TEMPLE_OF_SETHRALISS,
            self::THE_MOTHERLODE,
            self::THE_UNDERROT,
            self::TOL_DAGOR,
            self::WAYCREST_MANOR,
            self::MECHAGON_JUNKYARD,
            self::MECHAGON_WORKSHOP => Expansion::EXPANSION_BFA,
            self::DE_OTHER_SIDE,
            self::HALLS_OF_ATONEMENT,
            self::MISTS_OF_TIRNA_SCITHE,
            self::PLAGUEFALL,
            self::SANGUINE_DEPTHS,
            self::SPIRES_OF_ASCENSION,
            self::THE_NECROTIC_WAKE,
            self::THEATER_OF_PAIN,
            self::TAZAVESH_STREETS_OF_WONDER,
            self::TAZAVESH_SO_LEAHS_GAMBIT => Expansion::EXPANSION_SHADOWLANDS,
            self::BRACKENHIDE_HOLLOW,
            self::HALLS_OF_INFUSION,
            self::NELTHARUS,
            self::RUBY_LIFE_POOLS,
            self::ALGETH_AR_ACADEMY,
            self::THE_AZURE_VAULT,
            self::THE_NOKHUD_OFFENSIVE,
            self::ULDAMAN_LEGACY_OF_TYR,
            self::DAWN_OF_THE_INFINITE_GALAKRONDS_FALL,
            self::DAWN_OF_THE_INFINITE_MUROZONDS_RISE => Expansion::EXPANSION_DRAGONFLIGHT,
            self::ARA_KARA_CITY_OF_ECHOES,
            self::CINDERBREW_MEADERY,
            self::CITY_OF_THREADS,
            self::DARKFLAME_CLEFT,
            self::PRIORY_OF_THE_SACRED_FLAME,
            self::THE_DAWNBREAKER,
            self::THE_ROOKERY,
            self::THE_STONEVAULT,
            self::OPERATION_FLOODGATE,
            self::ECO_DOME_AL_DANI => Expansion::EXPANSION_TWW,
            self::DEN_OF_NALORAKK,
            self::MAGISTERS_TERRACE_MIDNIGHT,
            self::MAISARA_CAVERNS,
            self::MURDER_ROW,
            self::NEXUS_POINT_XENAS,
            self::THE_BLINDING_VALE,
            self::VOIDSCAR_ARENA,
            self::WINDRUNNER_SPIRE,
            self::ALGETH_AR_ACADEMY_MIDNIGHT,
            self::ALTAR_OF_FANGS => Expansion::EXPANSION_MIDNIGHT,
        };
    }

    /**
     * All cases of this enum, grouped by the expansion key they belong to.
     *
     * @return array<string, list<self>>
     */
    public static function casesByExpansionKey(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[$case->expansionKey()][] = $case;
        }

        return $result;
    }
}
