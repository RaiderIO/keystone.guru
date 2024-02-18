<?php

namespace App\Models;

use App\Models\Traits\HasIconFile;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property int        $id
 * @property int        $character_class_id
 * @property int        $icon_file_id
 * @property string     $key
 * @property string     $name
 * @property Collection $specializations
 *
 * @mixin Eloquent
 */
class CharacterClassSpecialization extends CacheModel
{
    use SeederModel;
    use HasIconFile;

    public $timestamps = false;
    public $hidden     = ['icon_file_id', 'pivot'];
    public $fillable   = ['key', 'name', 'character_class_id', 'icon_file_id'];

    /**
     * @return BelongsTo
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(CharacterClass::class);
    }
}
