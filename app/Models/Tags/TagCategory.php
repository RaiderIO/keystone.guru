<?php

namespace App\Models\Tags;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $name
 * @property string $model_class
 *
 * @mixin Eloquent
 */
class TagCategory extends Model
{
    use SeederModel;

    public $timestamps = false;

    public const DUNGEON_ROUTE_PERSONAL = 'dungeon_route_personal';

    public const DUNGEON_ROUTE_TEAM = 'dungeon_route_team';

    public const ALL = [
        self::DUNGEON_ROUTE_PERSONAL => 1,
        self::DUNGEON_ROUTE_TEAM     => 2,
    ];

    protected $fillable = ['id', 'name', 'model_class'];

    /**
     * https://stackoverflow.com/a/34485411/771270
     */
    public function getRouteKeyName(): string
    {
        return 'category';
    }
}
