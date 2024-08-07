<?php

namespace App\Models\Spell;

use App\Models\CacheModel;
use App\Models\Dungeon;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Str;

/**
 * @property int                      $id
 * @property string|null              $category
 * @property string|null              $cooldown_group
 * @property string                   $dispel_type
 * @property string                   $mechanic
 * @property string                   $icon_name
 * @property string                   $name
 * @property int                      $schools_mask
 * @property bool                     $aura Whenever it's a beneficial spell on a friendly target (extracted from CombatLogs)
 * @property bool                     $debuff Whenever it's a harmful spell on a hostile target (extracted from CombatLogs)
 * @property int                      $cast_time
 * @property int                      $duration
 * @property bool                     $selectable
 * @property bool                     $hidden_on_map
 * @property Carbon                   $fetched_data_at
 *
 * @property string                   $icon_url
 *
 * @property Collection<Dungeon>      $dungeons
 * @property Collection<SpellDungeon> $spellDungeons
 *
 * @method static Builder visible()
 *
 * @mixin Eloquent
 */
class Spell extends CacheModel implements MappingModelInterface
{
    use SeederModel;
    use SpellConstants;

    public $incrementing = false;

    public $timestamps = false;

    public $hidden = ['pivot'];

    protected $appends = ['icon_url'];

    protected $fillable = [
        'id',
        'category',
        'cooldown_group',
        'dispel_type',
        'mechanic',
        'icon_name',
        'name',
        'schools_mask',
        'aura',
        'debuff',
        'cast_time',
        'duration',
        'selectable',
        'hidden_on_map',
        'icon_url',
        'fetched_data_at',
    ];

    protected $casts = [
        'id'              => 'integer',
        'schools_mask'    => 'integer',
        'aura'            => 'boolean',
        'debuff'          => 'boolean',
        'cast_time'       => 'integer',
        'duration'        => 'integer',
        'selectable'      => 'boolean',
        'hidden_on_map'   => 'boolean',
        'fetched_data_at' => 'date',
    ];

    public function getSchoolsAsArray(): array
    {
        $result = [];

        foreach (self::ALL_SCHOOLS as $school) {
            $result[$school] = $this->schools_mask & $school;
        }

        return $result;
    }

    public function scopeVisible(): Builder
    {
        return $this->where('hidden_on_map', false);
    }

    public function dungeons(): HasManyThrough
    {
        return $this->hasManyThrough(Dungeon::class, SpellDungeon::class);
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
        return url(sprintf('/images/spells/%s.jpg', $this->icon_name));
    }

    public function getDungeonId(): ?int
    {
        // Spells aren't tied to a specific dungeon, but they're part of the mapping
        return 0;
    }

    public function getWowheadLink(): string
    {
        return sprintf('https://wowhead.com/spell=%d/%s', $this->id, Str::slug($this->name));
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

    public static function maskToReadableString(int $spellSchoolMask): string
    {
        $result = [];

        foreach (self::ALL_SCHOOLS as $schoolName => $schoolMask) {
            if ($spellSchoolMask & $schoolMask) {
                $result[] = $schoolName;
            }
        }

        return implode(', ', $result);
    }
}
