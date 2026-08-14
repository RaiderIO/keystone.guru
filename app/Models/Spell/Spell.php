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
use App\Service\Spell\Description\Dtos\RenderedSpellDescription;
use App\Service\Spell\Description\Dtos\SpellDescriptionValue;
use Carbon\Exceptions\InvalidFormatException;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Str;

/**
 * @property int                    $id
 * @property int                    $game_version_id
 * @property string|null            $category
 * @property string|null            $cooldown_group
 * @property string                 $dispel_type
 * @property string                 $mechanic
 * @property string                 $icon_name
 * @property string                 $name
 * @property string|null            $description_template
 * @property string|null            $description_format
 * @property array<int, mixed>|null $description_values
 *
 * @property string|null $description
 * @property int         $schools_mask
 * @property int         $miss_types_mask
 * @property int         $counters_mask
 * @property int         $bypasses_immunities_mask
 * @property bool        $aura                     Whenever it's a beneficial spell on a friendly target (extracted from CombatLogs)
 * @property bool|null   $debuff                   Whenever it's a harmful spell on a hostile target (extracted from CombatLogs)
 * @property int         $cast_time
 * @property int         $duration
 * @property bool        $selectable
 * @property bool        $hidden_on_map
 * @property int|null    $characteristic_id
 * @property Carbon      $fetched_data_at
 *
 * @property string $icon_url
 * @property string $wowhead_tooltip_data
 *
 * @property GameVersion                           $gameVersion
 * @property EloquentCollection<int, Dungeon>      $dungeons
 * @property EloquentCollection<int, SpellDungeon> $spellDungeons
 * @property EloquentCollection<int, SpellEffect>  $spellEffects
 * @property EloquentCollection<int, Npc>          $npcs
 * @property Characteristic|null                   $characteristic
 *
 * @method static Builder<self> visible()
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

    /** Dispel types that carry no information, and so earn no row in the tooltip. */
    private const array UNINFORMATIVE_DISPEL_TYPES = [
        null,
        '',
        self::DISPEL_TYPE_TRANSLATION_KEY_PREFIX . self::DISPEL_TYPE_NONE,
        self::DISPEL_TYPE_TRANSLATION_KEY_PREFIX . self::DISPEL_TYPE_NOT_AVAILABLE,
        self::DISPEL_TYPE_TRANSLATION_KEY_PREFIX . self::DISPEL_TYPE_UNKNOWN,
    ];

    protected $appends = [
        'icon_url',
        'wowhead_url',
        'wowhead_tooltip_data',
        'tooltip_data',
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
        'description_template',
        'description_format',
        'description_values',
        'damage_multiplier',
        'schools_mask',
        'miss_types_mask',
        'counters_mask',
        'bypasses_immunities_mask',
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
            'id'                       => 'integer',
            'game_version_id'          => 'integer',
            'schools_mask'             => 'integer',
            'miss_types_mask'          => 'integer',
            'counters_mask'            => 'integer',
            'bypasses_immunities_mask' => 'integer',
            'aura'                     => 'boolean',
            'debuff'                   => 'boolean',
            'cast_time'                => 'integer',
            'duration'                 => 'integer',
            'selectable'               => 'boolean',
            'hidden_on_map'            => 'boolean',
            'characteristic_id'        => 'integer',
            'fetched_data_at'          => 'datetime',
            'description_values'       => 'array',
            'damage_multiplier'        => 'float',
        ];
    }

    /**
     * The description as it reads with the values it was rendered with.
     *
     * Kept as an accessor rather than a column: the format and its values are the source of truth, so
     * that damage and healing can be recalculated for a key level without a stored sentence going stale.
     */
    public function getDescriptionAttribute(): ?string
    {
        if ($this->description_format === null) {
            return null;
        }

        return new RenderedSpellDescription(
            $this->description_format,
            array_map(SpellDescriptionValue::fromArray(...), $this->description_values ?? []),
        )->render();
    }

    /**
     * Everything the hover tooltip shows, in one payload.
     *
     * The description travels as its format plus its values rather than as a finished sentence, so the
     * browser can put different numbers in when a key level is picked (#3971). Null when we have no
     * description, which is what makes a link fall back to Wowhead's tooltip instead.
     *
     * @return array<string, mixed>|null
     */
    public function getTooltipDataAttribute(): ?array
    {
        if ($this->description_format === null) {
            return null;
        }

        return array_filter([
            'name'    => __($this->name),
            'format'  => $this->description_format,
            'values'  => $this->description_values ?? [],
            'schools' => self::maskToReadableString(self::ALL_SCHOOLS, $this->schools_mask, 'spellschools') ?: null,
            // A dispel type of none, n/a or unknown says nothing worth a row in the tooltip
            'dispelType' => in_array($this->dispel_type, self::UNINFORMATIVE_DISPEL_TYPES, true)
                ? null
                : __($this->dispel_type),
            'mechanic' => $this->mechanic ? __($this->mechanic) : null,
            'castTime' => $this->cast_time > 0 ? $this->cast_time / 1000 : null,
            'duration' => $this->duration > 0 ? $this->duration / 1000 : null,
        ], static fn(mixed $value): bool => $value !== null && $value !== []);
    }

    public function getWowheadUrlAttribute(): string
    {
        return self::getWowheadLink($this->game_version_id, $this->id, $this->name);
    }

    public function getWowheadTooltipDataAttribute(): string
    {
        return self::getWowheadTooltipData($this->game_version_id, $this->id);
    }

    public function setFetchedDataAtAttribute(mixed $value): void
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

    /** @return array<int, int> */
    public function getSchoolsAsArray(): array
    {
        $result = [];

        foreach (self::ALL_SCHOOLS as $school => $value) {
            $result[$school] = $this->schools_mask & $school;
        }

        return $result;
    }

    #[\Override]
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $id = (int)explode('-', (string)$value, 2)[0];

        /** @var static|null */
        return $this->where('id', $id)->first();
    }

    #[\Override]
    public function getRouteKey(): string
    {
        return sprintf('%d-%s', $this->id, Str::slug(__($this->name)));
    }

    /** @return Builder<self> */
    #[Scope]
    protected function visible(): Builder
    {
        return $this->where('hidden_on_map', false);
    }

    /** @return BelongsTo<Characteristic, $this> */
    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(Characteristic::class);
    }

    /** @return BelongsTo<GameVersion, $this> */
    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    /** @return BelongsToMany<Npc, $this> */
    public function npcs(): BelongsToMany
    {
        return $this->belongsToMany(Npc::class, 'npc_spells', 'spell_id', 'npc_id');
    }

    /** @return BelongsToMany<Dungeon, $this> */
    public function dungeons(): BelongsToMany
    {
        return $this->belongsToMany(Dungeon::class, 'spell_dungeons', 'spell_id', 'dungeon_id');
    }

    /** @return HasMany<SpellEffect, $this> */
    public function spellEffects(): HasMany
    {
        return $this->hasMany(SpellEffect::class);
    }

    /** @return HasMany<SpellDungeon, $this> */
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

    /**
     * The data-wowhead attribute value for the Wowhead tooltip script. The domain parameter must
     * match getWowheadLink()'s URL prefix for the same game version, or the hover tooltip shows
     * retail data while the link points at a classic database.
     */
    public static function getWowheadTooltipData(?int $gameVersionId, int $spellId): string
    {
        $domain = match ($gameVersionId) {
            GameVersion::ALL[GameVersion::GAME_VERSION_WRATH]       => 'wrath',
            GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA] => 'classic',
            GameVersion::ALL[GameVersion::GAME_VERSION_MOP]         => 'mop-classic',
            default                                                 => null,
        };

        return $domain === null
            ? sprintf('spell=%d', $spellId)
            : sprintf('spell=%d&domain=%s', $spellId, $domain);
    }

    /** @param array<int|string, int|string> $mapping */
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
                $result[] = $translationPrefix === null ? (string)$name : __($translationPrefix . '.' . $name);
            }
        }

        return implode(', ', $result);
    }
}
