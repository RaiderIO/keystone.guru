<?php

namespace App\Models;

use App\Models\Interfaces\CombatLogCriterionModelInterface;
use App\Models\Traits\HasCombatLogCriterion;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Str;

/**
 * @property int    $id
 * @property int    $character_class_id Internal ID - not a blizzard ID!
 * @property int    $specialization_id  Blizzard ID
 * @property string $icon_file_id       Vestigial - always -1. The icon itself is a static asset from
 *                                      the assets project (see icon_url), not an admin-editable File
 *                                      upload. Column kept for now (varchar(255) NOT NULL, no
 *                                      default); dropping it needs its own migration - see #3786.
 * @property string $key
 * @property string $name
 *
 * @property string $icon_url Appended
 *
 * @property CharacterClass $class
 *
 * @mixin Eloquent
 */
class CharacterClassSpecialization extends CacheModel implements CombatLogCriterionModelInterface
{
    use HasCombatLogCriterion;
    use SeederModel;

    public $timestamps = false;

    public $hidden = [
        'icon_file_id',
        'pivot',
    ];

    public $fillable = [
        'character_class_id',
        'specialization_id',
        'key',
        'name',
        'icon_file_id',
    ];

    /**
     * @return string
     */
    public function getIconUrlAttribute(): string
    {
        $className = Str::replace('_', '', $this->class->key);

        return ksgAssetImage(sprintf(
            '/specializations/%s/%s_%s.png',
            $className,
            $className,
            Str::replace('_', '', $this->key),
        ));
    }

    public function getName(): string
    {
        return sprintf('%s - %s', __($this->class->name), __($this->name));
    }

    public function getImageLink(): ?string
    {
        return $this->icon_url;
    }

    /** @return BelongsTo<CharacterClass, $this> */
    public function class(): BelongsTo
    {
        return $this->belongsTo(CharacterClass::class, 'character_class_id');
    }
}
