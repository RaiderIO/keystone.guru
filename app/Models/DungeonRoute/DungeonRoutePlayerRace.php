<?php

namespace App\Models\DungeonRoute;

use App\Models\CharacterRace;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @property int                                $id
 * @property int                                $dungeon_route_id
 * @property int                                $character_race_id
 * @property int                                $index
 * @property DungeonRoute                       $dungeonRoute
 * @property CharacterRace                      $characterRace
 * @property Collection<DungeonRoutePlayerRace> $races
 *
 * @mixin Eloquent
 */
class DungeonRoutePlayerRace extends Model
{
    public $hidden = ['id'];

    protected $fillable = [
        'dungeon_route_id',
        'character_race_id',
    ];

    public $timestamps = false;

    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function characterRace(): BelongsTo
    {
        return $this->belongsTo(CharacterRace::class);
    }

    public function races(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoutePlayerRace::class, 'dungeon_route_player_races');
    }
}
