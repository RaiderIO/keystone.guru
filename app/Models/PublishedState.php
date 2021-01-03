<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

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
    public const WORLD = 'world';
    public const WORLD_WITH_LINK = 'world_with_link';

    public const ALL = [
        self::UNPUBLISHED,
        self::TEAM,
        self::WORLD,
        self::WORLD_WITH_LINK
    ];

    public $timestamps = false;

    protected $fillable = [
        'name'
    ];

    protected $hidden = ['pivot'];

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
