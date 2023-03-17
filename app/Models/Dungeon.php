<?php

namespace App\Models;

use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Service\Season\SeasonServiceInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Mockery\Exception;

/**
 * @property int $id The ID of this Dungeon.
 * @property int $expansion_id The linked expansion to this dungeon.
 * @property int $zone_id The ID of the location that WoW has given this dungeon.
 * @property int $map_id The ID of the map (used internally in the game, used for simulation craft purposes)
 * @property int $mdt_id The ID that MDT has given this dungeon.
 * @property string $name The name of the dungeon.
 * @property string $slug The url friendly slug of the dungeon.
 * @property string $key Shorthand key of the dungeon
 * @property int $enemy_forces_required The amount of total enemy forces required to complete the dungeon.
 * @property int $enemy_forces_required_teeming The amount of total enemy forces required to complete the dungeon when Teeming is enabled.
 * @property int $enemy_forces_shrouded The amount of enemy forces a regular Shrouded enemy gives in this dungeon.
 * @property int $enemy_forces_shrouded_zul_gamux The amount of enemy forces the Zul'gamux Shrouded enemy gives in this dungeon.
 * @property int $timer_max_seconds The maximum timer (in seconds) that you have to complete the dungeon.
 * @property boolean $speedrun_enabled True if this dungeon has a speedrun enabled, false if it does not.
 * @property boolean $active True if this dungeon is active, false if it is not.
 *
 * @property Expansion $expansion
 *
 * @property Collection|MappingVersion[] $mappingversions
 * @property Collection|Floor[] $floors
 * @property Collection|DungeonRoute[] $dungeonroutes
 * @property Collection|Npc[] $npcs
 *
 * @property Collection|Enemy[] $enemies
 * @property Collection|EnemyPack[] $enemypacks
 * @property Collection|EnemyPatrol[] $enemypatrols
 * @property Collection|MapIcon[] $mapicons
 * @property Collection|DungeonFloorSwitchMarker[] $dungeonfloorswitchmarkers
 * @property Collection|MountableArea[] $mountableareas
 * @property Collection|DungeonSpeedrunRequiredNpc[] $dungeonSpeedrunRequiredNpcs10Man
 * @property Collection|DungeonSpeedrunRequiredNpc[] $dungeonSpeedrunRequiredNpcs25Man
 *
 * @method static Builder active()
 * @method static Builder inactive()
 *
 * @mixin Eloquent
 */
class Dungeon extends CacheModel implements MappingModelInterface
{
    const DIFFICULTY_10_MAN = 1;
    const DIFFICULTY_25_MAN = 2;

    const DIFFICULTY_ALL = [
        self::DIFFICULTY_10_MAN,
        self::DIFFICULTY_25_MAN,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['floor_count'];
    protected $fillable = [
        'active',
        'speedrun_enabled',
        'zone_id',
        'map_id',
        'mdt_id',
        'name',
        'key',
        'slug',
        'enemy_forces_required',
        'enemy_forces_required_teeming',
        'enemy_forces_shrouded',
        'enemy_forces_shrouded_zul_gamux',
        'timer_max_seconds',
    ];

    public $with = ['expansion', 'floors', 'dungeonSpeedrunRequiredNpcs10Man', 'dungeonSpeedrunRequiredNpcs25Man'];
    public $hidden = ['slug', 'active', 'mdt_id', 'zone_id', 'created_at', 'updated_at'];
    public $timestamps = false;


    // Classic
    const DUNGEON_BLACKFANTHOM_DEEPS        = 'blackfanthom_deeps';
    const DUNGEON_BLACKROCK_DEPTHS          = 'blackrock_depths';
    const DUNGEON_DEADMINES                 = 'deadmines';
    const DUNGEON_DIRE_MAUL                 = 'dire_maul';
    const DUNGEON_GNOMEREGAN                = 'gnomeregan';
    const DUNGEON_LOWER_BLACKROCK_SPIRE     = 'lower_blackrock_spire';
    const DUNGEON_MARAUDON                  = 'maraudon';
    const DUNGEON_RAGEFIRE_CHASM            = 'ragefire_chasm';
    const DUNGEON_RAZORFEN_DOWNS            = 'razorfen_downs';
    const DUNGEON_RAZORFEN_KRAUL            = 'razorfen_kraul';
    const DUNGEON_SCARLET_HALLS             = 'scarlet_halls';
    const DUNGEON_SCARLET_MONASTERY         = 'scarlet_monastery';
    const DUNGEON_SCHOLOMANCE               = 'scholomance';
    const DUNGEON_SHADOWFANG_KEEP           = 'shadowfang_keep';
    const DUNGEON_STRATHOLME                = 'stratholme';
    const DUNGEON_THE_STOCKADE              = 'the_stockade';
    const DUNGEON_THE_TEMPLE_OF_ATAL_HAKKAR = 'the_temple_of_atal_hakkar';
    const DUNGEON_ULDAMAN                   = 'uldaman';
    const DUNGEON_WAILING_CAVERNS           = 'wailing_caverns';
    const DUNGEON_ZUL_FARRAK                = 'zul_farrak';

    // The Burning Crusade
    const DUNGEON_ACHENAI_CRYPTS          = 'auchenai_crypts';
    const DUNGEON_HELLFIRE_RAMPARTS       = 'hellfire_ramparts';
    const DUNGEON_MAGISTERS_TERRACE       = 'magisters_terrace';
    const DUNGEON_MANA_TOMBS              = 'mana_tombs';
    const DUNGEON_OLD_HILLSBRAD_FOOTHILLS = 'old_hillsbrad_foothills';
    const DUNGEON_SETHEKK_HALLS           = 'sethekk_halls';
    const DUNGEON_SHADOW_LABYRINTH        = 'shadow_labyrinth';
    const DUNGEON_THE_ARCATRAZ            = 'the_arcatraz';
    const DUNGEON_THE_BLACK_MORASS        = 'the_black_morass';
    const DUNGEON_THE_BLOOD_FURNACE       = 'the_blood_furnace';
    const DUNGEON_THE_BOTANICA            = 'the_botanica';
    const DUNGEON_THE_MECHANAR            = 'the_mechanar';
    const DUNGEON_THE_SHATTERED_HALLS     = 'the_shattered_halls';
    const DUNGEON_THE_SLAVE_PENS          = 'the_slave_pens';
    const DUNGEON_THE_STEAMVAULT          = 'the_steamvault';
    const DUNGEON_THE_UNDERBOG            = 'the_underbog';

    // Wrath of the Lich King
    const DUNGEON_AHN_KAHET_THE_OLD_KINGDOM = 'ahnkahet';
    const DUNGEON_AZJOL_NERUB               = 'azjolnerub';
    const DUNGEON_DRAK_THARON_KEEP          = 'draktharonkeep';
    const DUNGEON_GUNDRAK                   = 'gundrak';
    const DUNGEON_HALLS_OF_LIGHTNING        = 'hallsoflightning';
    const DUNGEON_HALLS_OF_REFLECTION       = 'hallsofreflection';
    const DUNGEON_HALLS_OF_STONE            = 'hallsofstone'; // ulduar77
    const DUNGEON_PIT_OF_SARON              = 'pitofsaron';
    const DUNGEON_THE_CULLING_OF_STRATHOLME = 'thecullingofstratholme'; // cotstratholme
    const DUNGEON_THE_FORGE_OF_SOULS        = 'theforgeofsouls';
    const DUNGEON_THE_NEXUS                 = 'thenexus';
    const DUNGEON_THE_OCULUS                = 'theoculus'; // nexus80
    const DUNGEON_THE_VIOLET_HOLD           = 'theviolethold'; // violethold
    const DUNGEON_TRIAL_OF_THE_CHAMPION     = 'trialofthechampion'; // theargentcoliseum
    const DUNGEON_UTGARDE_KEEP              = 'utgardekeep';
    const DUNGEON_UTGARDE_PINNACLE          = 'utgardepinnacle';

    // Wrath of the Lich King Raid
    const RAID_ICECROWN_CITADEL                         = 'icecrowncitadel';
    const RAID_NAXXRAMAS                                = 'naxxramas';
    const RAID_ONYXIAS_LAIR                             = 'onyxiaslair';
    const RAID_CRUSADERS_COLISEUM_TRIAL_OF_THE_CRUSADER = 'theargentcoliseum';
    const RAID_THE_EYE_OF_ETERNITY                      = 'theeyeofeternity';
    const RAID_THE_OBSIDIAN_SANCTUM                     = 'theobsidiansanctum';
    const RAID_THE_RUBY_SANCTUM                         = 'therubysanctum';
    const RAID_ULDUAR                                   = 'ulduar';
    const RAID_VAULT_OF_ARCHAVON                        = 'vaultofarchavon';

    // Cataclysm
    const DUNGEON_BLACKROCK_CAVERNS        = 'blackrock_caverns';
    const DUNGEON_DEADMINES_CATACLYSM      = 'deadmines_cataclysm';
    const DUNGEON_END_TIME                 = 'end_time';
    const DUNGEON_GRIM_BATOL               = 'grim_batol';
    const DUNGEON_HALLS_OF_ORIGINATION     = 'halls_of_origination';
    const DUNGEON_HOUR_OF_TWILIGHT         = 'hour_of_twilight';
    const DUNGEON_LOST_CITY_OF_THE_TOL_VIR = 'lost_city_of_the_tol_vir';
    const DUNGEON_SHADOWFANG_KEEP_CATA     = 'shadowfang_keep_cataclysm';
    const DUNGEON_THE_STONECORE            = 'the_stonecore';
    const DUNGEON_THE_VORTEX_PINNACLE      = 'skywall';
    const DUNGEON_THRONE_OF_THE_TIDES      = 'throne_of_the_tides';
    const DUNGEON_WELL_OF_ETERNITY         = 'well_of_eternity';
    const DUNGEON_ZUL_AMAN                 = 'zul_aman';
    const DUNGEON_ZUL_GURUB                = 'zul_gurub';

    // Mists of Pandaria
    const DUNGEON_GATE_OF_THE_SETTING_SUN    = 'gate_of_the_setting_sun';
    const DUNGEON_MOGU_SHAN_PALACE           = 'mogu_shan palace';
    const DUNGEON_SCARLET_HALLS_MOP          = 'scarlet_halls_mop';
    const DUNGEON_SCARLET_MONASTERY_MOP      = 'scarlet_monastery_mop';
    const DUNGEON_SCHOLOMANCE_MOP            = 'scholomance_mop';
    const DUNGEON_SHADO_PAN_MONASTERY        = 'shado_pan_monastery';
    const DUNGEON_SIEGE_OF_NIUZAO_TEMPLE     = 'siege_of_niu_zao_temple';
    const DUNGEON_STORMSTOUT_BREWERY         = 'stormstout_brewery';
    const DUNGEON_TEMPLE_OF_THE_JADE_SERPENT = 'templeofthejadeserpent';

    // Warlords of Draenor
    const DUNGEON_AUCHINDOUN                = 'auchindoun';
    const DUNGEON_BLOODMAUL_SLAG_MINES      = 'bloodmaulslagmines';
    const DUNGEON_IRON_DOCKS                = 'irondocks';
    const DUNGEON_GRIMRAIL_DEPOT            = 'grimraildepot';
    const DUNGEON_SHADOWMOON_BURIAL_GROUNDS = 'shadowmoonburialgrounds';
    const DUNGEON_SKYREACH                  = 'skyreach';
    const DUNGEON_THE_EVERBLOOM             = 'theeverbloom';
    const DUNGEON_UPPER_BLACKROCK_SPIRE     = 'upperblackrockspire';

    // Legion
    const DUNGEON_ARCWAY                      = 'arcway';
    const DUNGEON_BLACK_ROOK_HOLD             = 'blackrookhold';
    const DUNGEON_CATHEDRAL_OF_ETERNAL_NIGHT  = 'cathedralofeternalnight';
    const DUNGEON_COURT_OF_STARS              = 'courtofstars';
    const DUNGEON_DARKHEART_THICKET           = 'darkheartthicket';
    const DUNGEON_EYE_OF_AZSHARA              = 'eyeofazshara';
    const DUNGEON_HALLS_OF_VALOR              = 'hallsofvalor';
    const DUNGEON_LOWER_KARAZHAN              = 'lowerkarazhan';
    const DUNGEON_MAW_OF_SOULS                = 'mawofsouls';
    const DUNGEON_NELTHARIONS_LAIR            = 'neltharionslair';
    const DUNGEON_UPPER_KARAZHAN              = 'upperkarazhan';
    const DUNGEON_THE_SEAT_OF_THE_TRIUMVIRATE = 'theseatofthetriumvirate';
    const DUNGEON_VAULT_OF_THE_WARDENS        = 'vaultofthewardens';

    // Battle for Azeroth
    const DUNGEON_ATAL_DAZAR           = 'ataldazar';
    const DUNGEON_FREEHOLD             = 'freehold';
    const DUNGEON_KINGS_REST           = 'kingsrest';
    const DUNGEON_SHRINE_OF_THE_STORM  = 'shrineofthestorm';
    const DUNGEON_SIEGE_OF_BORALUS     = 'siegeofboralus';
    const DUNGEON_TEMPLE_OF_SETHRALISS = 'templeofsethraliss';
    const DUNGEON_THE_MOTHERLODE       = 'themotherlode';
    const DUNGEON_THE_UNDERROT         = 'theunderrot';
    const DUNGEON_TOL_DAGOR            = 'toldagor';
    const DUNGEON_WAYCREST_MANOR       = 'waycrestmanor';
    const DUNGEON_MECHAGON_JUNKYARD    = 'mechagonjunkyard';
    const DUNGEON_MECHAGON_WORKSHOP    = 'mechagonworkshop';

    // Shadowlands
    const DUNGEON_DE_OTHER_SIDE              = 'deotherside_ardenweald';
    const DUNGEON_HALLS_OF_ATONEMENT         = 'hallsofatonement_a';
    const DUNGEON_MISTS_OF_TIRNA_SCITHE      = 'mistsoftirnescithe';
    const DUNGEON_PLAGUEFALL                 = 'plaguefall';
    const DUNGEON_SANGUINE_DEPTHS            = 'sanguinedepths_a';
    const DUNGEON_SPIRES_OF_ASCENSION        = 'spiresofascension_a';
    const DUNGEON_THE_NECROTIC_WAKE          = 'necroticwake_a';
    const DUNGEON_THEATER_OF_PAIN            = 'theaterofpain';
    const DUNGEON_TAZAVESH_STREETS_OF_WONDER = 'tazaveshstreetsofwonder';
    const DUNGEON_TAZAVESH_SO_LEAHS_GAMBIT   = 'tazaveshsoleahsgambit';

    // Dragonflight
    const DUNGEON_ALGETH_AR_ACADEMY     = 'dragonacademy';
    const DUNGEON_BRACKENHIDE_HOLLOW    = 'brackenhide';
    const DUNGEON_HALLS_OF_INFUSION     = 'hallsofinfusion';
    const DUNGEON_NELTHARUS             = 'neltharus';
    const DUNGEON_RUBY_LIFE_POOLS       = 'rubylifepools';
    const DUNGEON_THE_AZURE_VAULT       = 'theazurevault';
    const DUNGEON_THE_NOKHUD_OFFENSIVE  = 'nokhudoffensive';
    const DUNGEON_ULDAMAN_LEGACY_OF_TYR = 'uldamanlegacyoftyr';

    const ALL_WOTLK = [
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
    ];

    const ALL_WOTLK_RAID = [
        self::RAID_ICECROWN_CITADEL,
        self::RAID_NAXXRAMAS,
        self::RAID_ONYXIAS_LAIR,
        self::RAID_CRUSADERS_COLISEUM_TRIAL_OF_THE_CRUSADER,
        self::RAID_THE_EYE_OF_ETERNITY,
        self::RAID_THE_OBSIDIAN_SANCTUM,
        self::RAID_THE_RUBY_SANCTUM,
        self::RAID_ULDUAR,
        self::RAID_VAULT_OF_ARCHAVON,
    ];

    const ALL_WOD = [
        self::DUNGEON_IRON_DOCKS,
        self::DUNGEON_GRIMRAIL_DEPOT,
    ];

    const ALL_LEGION = [
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
    ];

    const ALL_BFA = [
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
    ];

    const ALL_SHADOWLANDS = [
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
    ];

    const ALL_DRAGONFLIGHT = [
        self::DUNGEON_BRACKENHIDE_HOLLOW,
        self::DUNGEON_HALLS_OF_INFUSION,
        self::DUNGEON_NELTHARUS,
        self::DUNGEON_RUBY_LIFE_POOLS,
        self::DUNGEON_ALGETH_AR_ACADEMY,
        self::DUNGEON_THE_AZURE_VAULT,
        self::DUNGEON_THE_NOKHUD_OFFENSIVE,
        self::DUNGEON_ULDAMAN_LEGACY_OF_TYR,
    ];

    /**
     * https://stackoverflow.com/a/34485411/771270
     * @return string
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
                    $npcs[$npc->id] = $npc->enemy_forces >= 0;
                }
            }
        } catch (Exception $ex) {
            dd($ex);
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

    /**
     * @return BelongsTo
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    /**
     * @return HasMany
     */
    public function mappingversions(): HasMany
    {
        return $this->hasMany(MappingVersion::class)->orderByDesc('mapping_versions.version');
    }

    /**
     * @return HasMany
     */
    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class)->orderByDesc('default')->orderBy('index');
    }

    /**
     * @return HasMany
     */
    public function dungeonroutes(): HasMany
    {
        return $this->hasMany(DungeonRoute::class);
    }

    /**
     * @param bool $includeGlobalNpcs
     * @return HasMany
     */
    public function npcs(bool $includeGlobalNpcs = true): HasMany
    {
        return $this->hasMany(Npc::class)
            ->when($includeGlobalNpcs, function (Builder $builder) {
                $builder->orWhere('dungeon_id', -1);
            });
    }

    /**
     * @return HasManyThrough
     */
    public function enemies(): HasManyThrough
    {
        return $this->hasManyThrough(Enemy::class, Floor::class);
    }

    /**
     * @return HasManyThrough
     */
    public function enemypacks(): HasManyThrough
    {
        return $this->hasManyThrough(EnemyPack::class, Floor::class);
    }

    /**
     * @return HasManyThrough
     */
    public function enemypatrols(): HasManyThrough
    {
        return $this->hasManyThrough(EnemyPatrol::class, Floor::class);
    }

    /**
     * @return HasManyThrough
     */
    public function mapicons(): HasManyThrough
    {
        return $this->hasManyThrough(MapIcon::class, Floor::class)
            ->where(function (Builder $builder) {
                return $builder
                    ->whereNull('dungeon_route_id');
            });
    }

    /**
     * @return HasManyThrough
     */
    public function dungeonfloorswitchmarkers(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonFloorSwitchMarker::class, Floor::class);
    }

    /**
     * @return HasManyThrough
     */
    public function mountableareas(): HasManyThrough
    {
        return $this->hasManyThrough(MountableArea::class, Floor::class);
    }

    /**
     * @return HasManyThrough
     */
    public function dungeonSpeedrunRequiredNpcs10Man(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonSpeedrunRequiredNpc::class, Floor::class)
            ->where('difficulty', Dungeon::DIFFICULTY_10_MAN);
    }

    /**
     * @return HasManyThrough
     */
    public function dungeonSpeedrunRequiredNpcs25Man(): HasManyThrough
    {
        return $this->hasManyThrough(DungeonSpeedrunRequiredNpc::class, Floor::class)
            ->where('difficulty', Dungeon::DIFFICULTY_25_MAN);
    }

    /**
     * Scope a query to only the Siege of Boralus dungeon.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFactionSelectionRequired(Builder $query): Builder
    {
        return $query->whereIn('key', [self::DUNGEON_SIEGE_OF_BORALUS, self::DUNGEON_THE_NEXUS]);
    }

    /**
     * Scope a query to only include active dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('dungeons.active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('dungeons.active', 0);
    }

    /**
     * @return MappingVersion|null
     */
    public function getCurrentMappingVersion(): ?MappingVersion
    {
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $this->mappingversions()->limit(1)->first();
        return $mappingVersion;
    }

    /**
     * @return MapIcon|null
     */
    public function getDungeonStart(): ?MapIcon
    {
        $result = null;

        foreach ($this->floors as $floor) {
            foreach ($floor->mapicons as $mapicon) {
                if ($mapicon->map_icon_type_id === MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START]) {
                    $result = $mapicon;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get the season that is active for this dungeon right now (preferring upcoming seasons if current and next season overlap)
     * @param SeasonServiceInterface $seasonService
     * @return Season|null
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

    /**
     * Get the minimum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMinHealth(): int
    {
        return $this->npcs(false)->where('classification_id', '<', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS])
            ->where('aggressiveness', '<>', 'friendly')
            ->when(!in_array($this->key, [Dungeon::RAID_NAXXRAMAS, Dungeon::RAID_ULDUAR]), function (Builder $builder) {
                // @TODO This should exclude all raids
                return $builder->where('enemy_forces', '>', 0);
            })
            ->min('base_health') ?? 10000;
    }

    /**
     * Get the maximum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMaxHealth(): int
    {
        return $this->npcs(false)->where('classification_id', '<', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS])
            ->where('aggressiveness', '<>', 'friendly')
            ->when(!in_array($this->key, [Dungeon::RAID_NAXXRAMAS, Dungeon::RAID_ULDUAR]), function (Builder $builder) {
                // @TODO This should exclude all raids
                return $builder->where('enemy_forces', '>', 0);
            })
            ->max('base_health') ?? 100000;
    }

    /**
     * @return bool
     */
    public function isFactionSelectionRequired(): bool
    {
        return in_array($this->key, [self::DUNGEON_SIEGE_OF_BORALUS, self::DUNGEON_THE_NEXUS]);
    }

    /**
     * Checks if this dungeon is Siege of Boralus. It's a bit of a special dungeon because of horde/alliance differences,
     * hence this function, so we can use it to differentiate between the two.
     *
     * @return bool
     */
    public function isSiegeOfBoralus(): bool
    {
        return $this->key === self::DUNGEON_SIEGE_OF_BORALUS;
    }

    /**
     * Checks if this dungeon is Tol Dagor. It's a bit of a special dungeon because of a shitty MDT bug.
     *
     * @return bool
     */
    public function isTolDagor(): bool
    {
        return $this->key === self::DUNGEON_TOL_DAGOR;
    }

    /**
     * @return int
     */
    public function getTimerUpgradePlusTwoSeconds(): int
    {
        return $this->timer_max_seconds * config('keystoneguru.keystone.timer.plustwofactor');
    }

    /**
     * @return int
     */
    public function getTimerUpgradePlusThreeSeconds(): int
    {
        return $this->timer_max_seconds * config('keystoneguru.keystone.timer.plusthreefactor');
    }

    /**
     * @return string
     */
    public function getImageUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s.jpg', $this->expansion->shortname, $this->key));
    }

    /**
     * @return string
     */
    public function getImage32Url(): string
    {
        return url(sprintf('images/dungeons/%s/%s_3-2.jpg', $this->expansion->shortname, $this->key));
    }

    /**
     * @return string
     */
    public function getImageTransparentUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s_transparent.png', $this->expansion->shortname, $this->key));
    }

    /**
     * @return string
     */
    public function getImageWallpaperUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s_wallpaper.jpg', $this->expansion->shortname, $this->key));
    }

    /**
     * @return bool
     */
    public function hasImageWallpaper(): bool
    {
        return file_exists(resource_path(sprintf('assets/images/dungeons/%s/%s_wallpaper.jpg', $this->expansion->shortname, $this->key)));
    }


    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }

    /**
     * @return int|null
     */
    public function getDungeonId(): ?int
    {
        return $this->id;
    }
}
