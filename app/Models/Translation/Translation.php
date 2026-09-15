<?php

namespace App\Models\Translation;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $locale
 * @property string $key
 * @property string $translation
 *
 * @mixin Eloquent
 */
class Translation extends Model
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'locale',
        'key',
        'translation',
    ];
}
