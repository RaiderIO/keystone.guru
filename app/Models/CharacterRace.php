<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int        $id
 * @property string     $key
 * @property string     $name
 * @property int        $faction_id
 * @property Faction    $faction
 * @property Collection $classes
 * @property Collection $specializations
 *
 * @mixin Eloquent
 */
class CharacterRace extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    public $hidden = ['icon_file_id', 'pivot'];

    public $fillable = ['key', 'name'];

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(CharacterClass::class, 'character_race_class_couplings');
    }

    public function specializations(): HasMany
    {
        return $this->hasMany(CharacterClass::class);
    }

    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    public function dungeonRoutePlayerRace(): HasMany
    {
        return $this->hasMany(DungeonRoutePlayerRace::class);
    }
}
