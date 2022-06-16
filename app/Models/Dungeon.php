<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mockery\Exception;

/**
 * @property int $id The ID of this Dungeon.
 * @property int $expansion_id The linked expansion to this dungeon.
 * @property int $zone_id The ID of the location that WoW has given this dungeon.
 * @property int $mdt_id The ID that MDT has given this dungeon.
 * @property string $name The name of the dungeon.
 * @property string $slug The url friendly slug of the dungeon.
 * @property string $key Shorthand key of the dungeon
 * @property int $enemy_forces_required The amount of total enemy forces required to complete the dungeon.
 * @property int $enemy_forces_required_teeming The amount of total enemy forces required to complete the dungeon when Teeming is enabled.
 * @property int $timer_max_seconds The maximum timer (in seconds) that you have to complete the dungeon.
 * @property boolean $active True if this dungeon is active, false if it is not.
 *
 * @property Expansion $expansion
 *
 * @property Collection|Floor[] $floors
 * @property Collection|DungeonRoute[] $dungeonroutes
 * @property Collection|Npc[] $npcs
 *
 * @property Collection|Enemy[] $enemies
 * @property Collection|EnemyPack[] $enemypacks
 * @property Collection|EnemyPatrol[] $enemypatrols
 * @property Collection|MapIcon[] $mapicons
 * @property Collection|DungeonFloorSwitchMarker[] $floorswitchmarkers
 *
 * @method static Builder active()
 * @method static Builder inactive()
 *
 * @mixin Eloquent
 */
class Dungeon extends CacheModel
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['floor_count'];
    public $with = ['expansion', 'floors'];

    public $hidden = ['slug', 'active', 'mdt_id', 'zone_id', 'created_at', 'updated_at'];
    public $timestamps = false;

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
        return $this->belongsTo('App\Models\Expansion');
    }

    /**
     * @return HasMany
     */
    public function floors(): HasMany
    {
        return $this->hasMany('App\Models\Floor')->orderBy('index');
    }

    /**
     * @return HasMany
     */
    public function dungeonroutes(): HasMany
    {
        return $this->hasMany('App\Models\DungeonRoute');
    }

    /**
     * @param bool $includeGlobalNpcs
     * @return HasMany
     */
    public function npcs(bool $includeGlobalNpcs = true): HasMany
    {
        return $this->hasMany('App\Models\Npc')
            ->when($includeGlobalNpcs, function(Builder $builder){
                $builder->orWhere('dungeon_id', -1);
            });
    }

    /**
     * @return HasManyThrough
     */
    public function enemies(): HasManyThrough
    {
        return $this->hasManyThrough('App\Models\Enemy', 'App\Models\Floor');
    }

    /**
     * @return HasManyThrough
     */
    public function enemypacks(): HasManyThrough
    {
        return $this->hasManyThrough('App\Models\EnemyPack', 'App\Models\Floor');
    }

    /**
     * @return HasManyThrough
     */
    public function enemypatrols(): HasManyThrough
    {
        return $this->hasManyThrough('App\Models\EnemyPatrol', 'App\Models\Floor');
    }

    /**
     * @return HasManyThrough
     */
    public function mapicons(): HasManyThrough
    {
        return $this->hasManyThrough('App\Models\MapIcon', 'App\Models\Floor')->where('dungeon_route_id', -1);
    }

    /**
     * @return HasManyThrough
     */
    public function floorswitchmarkers(): HasManyThrough
    {
        return $this->hasManyThrough('App\Models\DungeonFloorSwitchMarker', 'App\Models\Floor');
    }

    /**
     * Scope a query to only the Siege of Boralus dungeon.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSiegeOfBoralus($query)
    {
        return $query->where('key', self::DUNGEON_SIEGE_OF_BORALUS);
    }

    /**
     * Scope a query to only include active dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('dungeons.active', 1);
    }

    /**
     * Scope a query to only include inactive dungeons.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('dungeons.active', 0);
    }


    /**
     * Get the minimum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMinHealth(): int
    {
        return $this->npcs(false)->where('classification_id', '<', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS])
                ->where('aggressiveness', '<>', 'friendly')
                ->where('enemy_forces', '>', 0)
                ->min('base_health') ?? 10000;
    }

    /**
     * Get the maximum amount of health of all NPCs in this dungeon.
     */
    public function getNpcsMaxHealth(): int
    {
        return $this->npcs(false)->where('classification_id', '<', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS])
                ->where('aggressiveness', '<>', 'friendly')
                ->where('enemy_forces', '>', 0)
                ->max('base_health') ?? 100000;
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
        return $this->timer_max_seconds * config('keystoneguru.timer.plustwofactor');
    }

    /**
     * @return int
     */
    public function getTimerUpgradePlusThreeSeconds(): int
    {
        return $this->timer_max_seconds * config('keystoneguru.timer.plusthreefactor');
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
        return url(sprintf('images/dungeons/%s/%s_transparent.jpg', $this->expansion->shortname, $this->key));
    }

    /**
     * @return string
     */
    public function getImageWallpaperUrl(): string
    {
        return url(sprintf('images/dungeons/%s/%s_wallpaper.jpg', $this->expansion->shortname, $this->key));
    }


    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
