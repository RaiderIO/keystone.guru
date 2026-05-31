<?php

namespace App\Models\Spell;

use App\Models\CacheModel;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Npc\Npc;
use App\Models\Traits\SeederModel;
use App\Models\Traits\SerializesDates;
use Carbon\Exceptions\InvalidFormatException;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Str;

/**
 * @property int         $id
 * @property int         $game_version_id
 * @property string|null $category
 * @property string|null $cooldown_group
 * @property string      $dispel_type
 * @property string      $mechanic
 * @property string      $icon_name
 * @property string      $name
 * @property int         $schools_mask
 * @property int         $miss_types_mask
 * @property bool        $aura              Whenever it's a beneficial spell on a friendly target (extracted from CombatLogs)
 * @property bool|null   $debuff            Whenever it's a harmful spell on a hostile target (extracted from CombatLogs)
 * @property int         $cast_time
 * @property int         $duration
 * @property bool        $selectable
 * @property bool        $hidden_on_map
 * @property int|null    $characteristic_id
 * @property Carbon      $fetched_data_at
 *
 * @property string $icon_url
 *
 * @property GameVersion              $gameVersion
 * @property Collection<int, Dungeon> $dungeons
 * @property Collection<SpellDungeon> $spellDungeons
 * @property Collection<Npc>          $npcs
 * @property Characteristic|null      $characteristic
 *
 * @method static Builder visible()
 *
 * @mixin Eloquent
 */
class Spell extends CacheModel implements MappingModelInterface
{
    use SeederModel;
    use SpellConstants;
    use SerializesDates;

    public $incrementing = false;

    public $timestamps = false;

    public $hidden = ['pivot'];

    protected $appends = [
        'icon_url',
        'wowhead_url',
    ];

    protected $fillable = [
        'id',
        'game_version_id',
        'category',
        'cooldown_group',
        'dispel_type',
        'mechanic',
        'icon_name',
        'name',
        'schools_mask',
        'miss_types_mask',
        'aura',
        'debuff',
        'cast_time',
        'duration',
        'selectable',
        'hidden_on_map',
        'characteristic_id',
        'icon_url',
        'fetched_data_at',
    ];

    protected function casts(): array
    {
        return [
            'id'                => 'integer',
            'game_version_id'   => 'integer',
            'schools_mask'      => 'integer',
            'miss_types_mask'   => 'integer',
            'aura'              => 'boolean',
            'debuff'            => 'boolean',
            'cast_time'         => 'integer',
            'duration'          => 'integer',
            'selectable'        => 'boolean',
            'hidden_on_map'     => 'boolean',
            'characteristic_id' => 'integer',
            'fetched_data_at'   => 'datetime',
        ];
    }

    public function getWowheadUrlAttribute(): string
    {
        return self::getWowheadLink($this->game_version_id, $this->id, $this->name);
    }

    public function setFetchedDataAtAttribute($value): void
    {
        if (is_string($value)) {
            try {
                $this->attributes['fetched_data_at'] = Carbon::createFromFormat(self::SERIALIZED_DATE_TIME_FORMAT, $value);
            } catch (InvalidFormatException) {
                $this->attributes['fetched_data_at'] = Carbon::createFromFormat(self::DATABASE_DATE_TIME_FORMAT, $value);
            }
        } else {
            $this->attributes['fetched_data_at'] = $value;
        }
    }

    public function getSchoolsAsArray(): array
    {
        $result = [];

        foreach (self::ALL_SCHOOLS as $school => $value) {
            $result[$school] = $this->schools_mask & $school;
        }

        return $result;
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $id = (int)explode('-', (string)$value, 2)[0];

        return $this->where('id', $id)->first();
    }

    public function getRouteKey(): string
    {
        return sprintf('%d-%s', $this->id, Str::slug(__($this->name)));
    }

    #[Scope]
    protected function visible(): Builder
    {
        return $this->where('hidden_on_map', false);
    }

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(Characteristic::class);
    }

    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    public function npcs(): BelongsToMany
    {
        return $this->belongsToMany(Npc::class, 'npc_spells', 'spell_id', 'npc_id');
    }

    public function dungeons(): BelongsToMany
    {
        return $this->belongsToMany(Dungeon::class, 'spell_dungeons', 'spell_id', 'dungeon_id');
    }

    public function spellDungeons(): HasMany
    {
        return $this->hasMany(SpellDungeon::class);
    }

    /**
     * @return string
     */
    public function getIconUrlAttribute(): string
    {
        return ksgAssetImage(sprintf('spells/%s.jpg', $this->icon_name));
    }

    public function getDungeonId(): ?int
    {
        // Spells aren't tied to a specific dungeon, but they're part of the mapping
        return 0;
    }

    public function isAssignedDungeon(Dungeon $dungeon): bool
    {
        $result = false;
        foreach ($this->spellDungeons as $spellDungeon) {
            if ($spellDungeon->dungeon_id === $dungeon->id) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    public static function getWowheadLink(?int $gameVersionId, int $spellId, ?string $name = null): string
    {
        $wowheadBaseUrl = 'https://www.wowhead.com';
        if ($gameVersionId !== null) {
            switch ($gameVersionId) {
                case GameVersion::ALL[GameVersion::GAME_VERSION_WRATH]:
                    $wowheadBaseUrl .= '/wrath';
                    break;
                case GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]:
                    $wowheadBaseUrl .= '/classic';
                    break;
                case GameVersion::ALL[GameVersion::GAME_VERSION_MOP]:
                    $wowheadBaseUrl .= '/mop-classic';
                    break;
            }
        }

        $result = sprintf('%s/spell=%d', $wowheadBaseUrl, $spellId);

        if (!empty(__($name))) {
            $result .= '/' . Str::slug(__($name));
        }

        return $result;
    }

    public static function maskToReadableString(array $mapping, int $mask, ?string $translationPrefix = null): string
    {
        $result = [];

        foreach ($mapping as $key => $value) {
            // New format: bitmask => name
            if (is_int($key)) {
                $bitmask = $key;
                $name    = $value;
            } // Old format: name => bitmask
            else {
                $bitmask = $value;
                $name    = $key;
            }

            if (($mask & $bitmask) !== 0) {
                $result[] = $translationPrefix === null ? $name : __($translationPrefix . '.' . $name);
            }
        }

        return implode(', ', $result);
    }
}
