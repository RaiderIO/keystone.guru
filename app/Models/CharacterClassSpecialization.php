<?php

namespace App\Models;

use App\Models\Traits\HasIconFile;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Str;

/**
 * @property int            $id
 * @property int            $character_class_id Internal ID - not a blizzard ID!
 * @property int            $specialization_id Blizzard ID
 * @property int            $icon_file_id
 * @property string         $key
 * @property string         $name
 *
 * @property string         $icon_url Appended
 *
 * @property CharacterClass $class
 *
 * @mixin Eloquent
 */
class CharacterClassSpecialization extends CacheModel
{
    use HasIconFile;
    use SeederModel;

    public $timestamps = false;

    public $hidden = ['icon_file_id', 'pivot'];

    public $fillable = ['character_class_id', 'specialization_id', 'key', 'name', 'icon_file_id'];

    /**
     * @return string
     */
    public function getIconUrlAttribute(): string
    {
        $className = Str::replace('_', '', $this->class->key);
        return url(sprintf('/images/specializations/%s/%s_%s.png',
            $className,
            $className,
            Str::replace('_', '', $this->key)
        ));
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(CharacterClass::class, 'character_class_id');
    }
}
