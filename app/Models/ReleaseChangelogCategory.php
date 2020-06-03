<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $category
 *
 * @mixin \Eloquent
 */
class ReleaseChangelogCategory extends Model
{
    public $table = 'release_changelog_categories';
    public $timestamps = false;
    protected $visible = ['id', 'category'];
    protected $fillable = ['category'];
}
