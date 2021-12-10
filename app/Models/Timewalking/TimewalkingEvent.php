<?php

namespace App\Models\Timewalking;

use App;
use App\Models\CacheModel;
use App\Models\Expansion;
use App\Models\Traits\HasStart;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property $id int The ID of this timewalking event.
 * @property $expansion_id int
 * @property $key string
 * @property $name string
 * @property $start Carbon
 * @property $start_duration_weeks int
 * @property $week_interval int
 *
 * @property Expansion $expansion
 * @property Collection|TimewalkingEventAffixGroup[] $timewalkingeventaffixgroups
 *
 * @mixin Eloquent
 */
class TimewalkingEvent extends CacheModel
{
    use HasStart;

    public $timestamps = false;

    const TIMEWALKING_EVENT_LEGION      = 'legion';
    const TIMEWALKING_EVENT_BFA         = 'bfa';
    const TIMEWALKING_EVENT_SHADOWLANDS = 'shadowlands';

    /**
     * @return BelongsTo
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo('App\Models\Expansion');
    }

    /**
     * @return HasMany
     */
    public function timewalkingeventaffixgroups(): HasMany
    {
        return $this->hasMany('App\Models\Timewalking\TimewalkingEventAffixGroup');
    }
}
