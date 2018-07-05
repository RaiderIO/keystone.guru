<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $character_race_id
 * @property int $character_class_id
 * @property \Illuminate\Support\Collection $specializations
 */
class CharacterRaceClassCoupling extends Model
{
    public $timestamps = false;

}
