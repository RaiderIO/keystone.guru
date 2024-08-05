<?php

namespace App\Models\Spell;

use App\Models\CacheModel;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Str;

/**
 * @property int    $id
 * @property string|null $category
 * @property string|null $cooldown_group
 * @property string $dispel_type
 * @property string $icon_name
 * @property string $name
 * @property int    $schools_mask
 * @property bool   $aura
 * @property bool   $selectable
 * @property bool   $hidden_on_map
 *
 * @property string $icon_url
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
        'icon_name',
        'name',
        'schools_mask',
        'aura',
        'selectable',
        'hidden_on_map',
        'icon_url',
    ];

    protected $casts = [
        'id'            => 'integer',
        'schools_mask'  => 'integer',
        'aura'          => 'boolean',
        'selectable'    => 'boolean',
        'hidden_on_map' => 'boolean',
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
