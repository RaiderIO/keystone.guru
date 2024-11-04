<?php

namespace App\Models\Translation;

use App\Models\AffixGroup\AffixGroup;
use App\Models\CacheModel;
use App\Models\Traits\HasIconFile;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Str;

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
