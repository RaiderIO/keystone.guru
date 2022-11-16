<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $dungeon_route_id
 * @property int $character_class_specialization_id
 * @property int $index
 *
 * @mixin Eloquent
 */
class DungeonRoutePlayerSpecialization extends Model
{
    public $hidden = ['id'];
    protected $fillable = [
        'character_class_specialization_id',
        'dungeon_route_id'
    ];

    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function dungeonroute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class, 'dungeon_route_id');
    }

    /**
     * @return BelongsTo
     */
    public function characterclassspecialization(): BelongsTo
    {
        return $this->belongsTo(CharacterClassSpecialization::class);
    }

    /**
     * @return BelongsToMany
     */
    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(DungeonRoutePlayerSpecialization::class, 'dungeon_route_player_specializations');
    }
}
