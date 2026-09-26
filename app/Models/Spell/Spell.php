<?php

namespace App\Models\Spell;

use App\Models\Characteristic;
use App\Models\CombatLog\SpellProperty;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Npc\Npc;
use App\Models\Traits\SeederModel;
use App\Models\Traits\SerializesDates;
use App\Service\Spell\Description\Dtos\RenderedSpellDescription;
use App\Service\Spell\Description\Dtos\SpellDescriptionValue;
use App\Service\WagoTools\GameLocale;
use Carbon\Exceptions\InvalidFormatException;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
 * @property bool        $is_pvp_talent
 * @property Carbon      $fetched_data_at
 *
 * @property string $icon_url
 * @property string $wowhead_tooltip_data
 *
 * @property GameVersion                                          $gameVersion
 * @property EloquentCollection<int, Dungeon>                     $dungeons
 * @property EloquentCollection<int, SpellDungeon>                $spellDungeons
 * @property SpellDescriptionTranslation|null                     $descriptionTranslation
 * @property EloquentCollection<int, SpellDescriptionTranslation> $descriptionTranslations
 * @property EloquentCollection<int, SpellEffect>                 $spellEffects
 * @property EloquentCollection<int, SpellTuningChange>           $tuningChanges
 * @property EloquentCollection<int, Npc>                         $npcs
 * @property Characteristic|null                                  $characteristic
 *
 * @method static Builder<self> visible()
 *
 * @mixin Eloquent
 */
class Spell extends Model implements MappingModelInterface
{
    use SeederModel;
    use SerializesDates;

    public $incrementing = false;

    public $timestamps = false;

    /**
     * The description translation relations are eager loaded wherever a tooltip is rendered, but never
     * serialized: what they hold already travels inside `tooltip_data`, and shipping it alongside would
     * send every description twice.
     */
    public $hidden = ['pivot', 'descriptionTranslation', 'descriptionTranslations'];

    /** Dispel types that carry no information, and so earn no row in the tooltip. */
    private const array UNINFORMATIVE_DISPEL_TYPES = [
        null,
        '',
        SpellDispelType::TRANSLATION_KEY_PREFIX . SpellDispelType::None->value,
        SpellDispelType::TRANSLATION_KEY_PREFIX . SpellDispelType::NotAvailable->value,
        SpellDispelType::TRANSLATION_KEY_PREFIX . SpellDispelType::Unknown->value,
    ];

    /**
     * Columns holding behavior derived from combat logs rather than from the game client, so their
     * values are per-environment and must never round-trip through the git seeders.
     *
     * Every entry must be hidden by MappingExportServiceInterface::serializeSpells() (so it stays out
     * of spells.json) *and* preserved by SpellRelationMapping::getPreservedColumns() (so a re-seed
     * copies the live value into the temp table instead of nulling it). Both read this one list: a
     * column missing from either is wiped from every environment by the next seed.
     */
    public const array COMBAT_LOG_DERIVED_COLUMNS = [
        'aura',
        'debuff',
        'miss_types_mask',
        'counters_mask',
        'bypasses_immunities_mask',
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
        'is_pvp_talent',
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
            'is_pvp_talent'            => 'boolean',
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
        return $this->getRenderedDescription(app()->getLocale())?->render();
    }

    /**
     * The description in `$locale`: the game client's own text for that locale when we imported one,
     * and the English description otherwise.
     */
    private function getRenderedDescription(string $locale): ?RenderedSpellDescription
    {
        // A spell selected without its description columns - a kill zone's spells, which carry an id and
        // an icon and nothing else - has no description to render, and so no translation to look up
        if (!array_key_exists('description_format', $this->attributes)) {
            return null;
        }

        $translation = $this->findDescriptionTranslation(GameLocale::forAppLocale($locale));

        if ($translation !== null) {
            return new RenderedSpellDescription(
                $translation->description_format,
                array_map(SpellDescriptionValue::fromArray(...), $translation->description_values ?? []),
            );
        }

        if ($this->description_format === null) {
            return null;
        }

        return new RenderedSpellDescription(
            $this->description_format,
            array_map(SpellDescriptionValue::fromArray(...), $this->description_values ?? []),
        );
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
        return $this->getTooltipData(app()->getLocale());
    }

    /**
     * The `tooltip_data` payload in an explicit locale rather than the application's.
     *
     * The description comes from `descriptionTranslations` when the caller eager loaded it (constrained to
     * `$locale`), so building the payload for another locale needs no change to the application locale.
     *
     * @return array<string, mixed>|null
     */
    public function getTooltipData(string $locale): ?array
    {
        $description = $this->getRenderedDescription($locale);

        if ($description === null) {
            return null;
        }

        return array_filter([
            'name'   => __($this->name, [], $locale),
            'format' => $description->format,
            'values' => array_map(
                static fn(SpellDescriptionValue $value): array => $value->toArray(),
                $description->values,
            ),
            'schools'    => SpellSchool::maskToTranslatedString($this->schools_mask, $locale) ?: null,
            'dispelType' => $this->hasUninformativeDispelType() ? null : __($this->dispel_type, [], $locale),
            'mechanic'   => $this->mechanic ? __($this->mechanic, [], $locale) : null,
            'castTime'   => $this->cast_time > 0 ? $this->cast_time / 1000 : null,
            'duration'   => $this->duration > 0 ? $this->duration / 1000 : null,
        ], static fn(mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * The imported description for `$gameLocale`, or null when there is none - always for English, whose
     * description lives on the spell itself, so no relation is read for an English visitor.
     *
     * Prefers `descriptionTranslations` when it was eager loaded (the caller constrains it to the locale
     * it builds for); otherwise `descriptionTranslation`, which is bound to the application locale, when
     * that is the locale asked for.
     */
    private function findDescriptionTranslation(GameLocale $gameLocale): ?SpellDescriptionTranslation
    {
        if ($gameLocale === GameLocale::English) {
            return null;
        }

        if (!$this->relationLoaded('descriptionTranslations')
            && $gameLocale === GameLocale::forAppLocale(app()->getLocale())) {
            return $this->descriptionTranslation;
        }

        return $this->descriptionTranslations->firstWhere('locale', $gameLocale->value);
    }

    /**
     * Whether `dispel_type` carries no information worth showing in the tooltip - a dispel type of
     * none, n/a or unknown, or a value that isn't a recognised prefixed translation key
     * (legacy/unprefixed data, drift).
     */
    private function hasUninformativeDispelType(): bool
    {
        return in_array($this->dispel_type, self::UNINFORMATIVE_DISPEL_TYPES, true)
            || !in_array($this->dispel_type, SpellDispelType::translationKeys(), true);
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

        foreach (SpellSchool::slugsByBit() as $school => $value) {
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

    /**
     * The description in the locale being viewed, when the game client publishes one for it. English is
     * not among them - it is stored on the spell itself - so this relation is empty for every English
     * visitor, and eager loading it in that case is a query for nothing.
     *
     * @return HasOne<SpellDescriptionTranslation, $this>
     */
    public function descriptionTranslation(): HasOne
    {
        return $this->hasOne(SpellDescriptionTranslation::class)
            ->where('locale', GameLocale::forAppLocale(app()->getLocale())->value);
    }

    /**
     * The description in every locale the game client publishes besides English. Eager load it with a
     * locale constraint to build a tooltip for a locale other than the application's.
     *
     * @return HasMany<SpellDescriptionTranslation, $this>
     */
    public function descriptionTranslations(): HasMany
    {
        return $this->hasMany(SpellDescriptionTranslation::class);
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

    /** @return HasMany<SpellTuningChange, $this> */
    public function tuningChanges(): HasMany
    {
        return $this->hasMany(SpellTuningChange::class);
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

    /**
     * Records a combat-log-derived property on this spell, and reports whether this call was the one that set it.
     *
     * The write is the guard: the UPDATE only matches rows where the property is still unset, so exactly one caller
     * ever gets `true` back and can emit the single `CombatLogSpellEvent` that belongs to that transition. Deciding
     * that by reading the property off the model first does not work - the extraction pipeline hands every job a
     * process-persistent spell catalog (#4058), so a snapshot taken before another worker's write keeps reporting the
     * property as unset for up to the catalog TTL, and every job in that window re-emitted the same event (#4199).
     *
     * The catalog's own copy is updated on success so the same process does not keep re-issuing the UPDATE.
     */
    public function recordCombatLogProperty(SpellProperty $property): bool
    {
        $column = $property->column();
        $bit    = $property->maskBit();

        $query = self::query()->where('id', $this->id);

        if ($bit === null) {
            // `debuff` is nullable, so a bare where(false) would never match the rows that still hold NULL
            $query->where(static fn(Builder $builder) => $builder->where($column, false)->orWhereNull($column));
            $values = [$column => true];
        } else {
            $query->whereRaw(sprintf('%s & ? = 0', $column), [$bit]);
            $values = [$column => DB::raw(sprintf('%s | %d', $column, $bit))];
        }

        if ($query->update($values) !== 1) {
            return false;
        }

        $this->setAttribute($column, $bit === null ? true : ((int)$this->getAttribute($column) | $bit));
        $this->syncOriginalAttribute($column);

        return true;
    }

    public static function getWowheadLink(?int $gameVersionId, int $spellId, ?string $name = null): string
    {
        $result = sprintf('%s/spell=%d', GameVersion::getWowheadBaseUrl($gameVersionId), $spellId);

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
        $domain = GameVersion::getWowheadDomain($gameVersionId);

        return $domain === null
            ? sprintf('spell=%d', $spellId)
            : sprintf('spell=%d&domain=%s', $spellId, $domain);
    }
}
