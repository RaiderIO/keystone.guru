<?php

namespace App\Models\Translation;

use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
use Eloquent;

/**
 * @property int    $id
 * @property string $locale
 * @property string $key
 * @property string $translation
 *
 * @mixin Eloquent
 */
class Translation extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = ['id', 'locale', 'key', 'translation'];
}
