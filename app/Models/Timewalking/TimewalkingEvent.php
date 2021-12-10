<?php

namespace App\Models\Timewalking;

use App;
use App\Models\CacheModel;
use App\Models\Expansion;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 *
 * @mixin Eloquent
 */
class TimewalkingEvent extends CacheModel
{
    public $timestamps = false;

    const TIMEWALKING_EVENT_LEGION = 'legion';
    const TIMEWALKING_EVENT_BFA = 'bfa';
    const TIMEWALKING_EVENT_SHADOWLANDS = 'shadowlands';

    /**
     * @return BelongsTo
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo('App\Models\Expansion');
    }
}
