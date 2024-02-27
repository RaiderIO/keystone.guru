<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $character_race_id
 * @property int $character_class_id
 * @property Collection $specializations
 *
 * @mixin Eloquent
 */
class CharacterRaceClassCoupling extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'character_race_id',
        'character_class_id',
    ];
}
