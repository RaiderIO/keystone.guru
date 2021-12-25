<?php

namespace App\Models\Tags;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $model_class
 *
 * @mixin Eloquent
 */
class TagCategory extends Model
{
    public $timestamps = false;

    const DUNGEON_ROUTE_PERSONAL = 'dungeon_route_personal';
    const DUNGEON_ROUTE_TEAM     = 'dungeon_route_team';

    const ALL = [
        self::DUNGEON_ROUTE_PERSONAL => 1,
        self::DUNGEON_ROUTE_TEAM     => 2,
    ];

    protected $fillable = ['id', 'name', 'model_class'];

    /**
     * https://stackoverflow.com/a/34485411/771270
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'category';
    }
}
