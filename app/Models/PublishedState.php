<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property $id int
 * @property $name string
 *
 * @mixin Eloquent
 */
class PublishedState extends Model
{
    public const UNPUBLISHED = 'unpublished';
    public const TEAM = 'team';
    public const WORLD_WITH_LINK = 'world_with_link';
    public const WORLD = 'world';

    public const ALL = [
        self::UNPUBLISHED,
        self::TEAM,
        self::WORLD_WITH_LINK,
        self::WORLD,
    ];

    public $timestamps = false;

    protected $fillable = [
        'name'
    ];

    protected $hidden = ['pivot'];

    /**
     * @return HasMany
     */
    public function dungeonroutes()
    {
        return $this->hasMany('App\Models\DungeonRoute');
    }

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel)
        {
            return false;
        });
    }
}
