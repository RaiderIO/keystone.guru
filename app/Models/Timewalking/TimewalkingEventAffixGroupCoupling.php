<?php

namespace App\Models\Timewalking;

use App\Models\Affix;
use App\Models\CacheModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property $id int The ID of this Affix.
 * @property $affix_id int
 * @property $timewalking_event_affix_group_id int
 *
 * @property Affix $affix
 * @property TimewalkingEventAffixGroup $timewalkingeventaffixgroup
 *
 * @mixin Eloquent
 */
class TimewalkingEventAffixGroupCoupling extends CacheModel
{
    public $timestamps = false;
    protected $fillable = ['affix_id', 'timewalking_event_affix_group_id'];

    /**
     * @return HasOne
     */
    public function affix(): HasOne
    {
        return $this->hasOne('App\Models\Affix');
    }

    /**
     * @return HasOne
     */
    public function timewalkingeventaffixgroup(): HasOne
    {
        return $this->hasOne('App\Models\Timewalking\TimewalkingEventAffixGroup');
    }
}
