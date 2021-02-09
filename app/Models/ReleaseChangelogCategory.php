<?php

namespace App\Models;

/**
 * @property int $id
 * @property string $category
 *
 * @mixin \Eloquent
 */
class ReleaseChangelogCategory extends CacheModel
{
    public $table = 'release_changelog_categories';
    public $timestamps = false;
    protected $visible = ['id', 'category'];
    protected $fillable = ['category'];
}
