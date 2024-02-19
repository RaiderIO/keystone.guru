<?php

namespace App\Models\Timewalking;

use App\Models\CacheModel;
use App\Models\Expansion;
use App\Models\Season;
use App\Models\Traits\HasStart;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int       $id The ID of this timewalking event.
 * @property int       $expansion_id
 * @property int       $season_id
 * @property string    $key
 * @property string    $name
 * @property Carbon    $start
 * @property int       $start_duration_weeks
 * @property int       $week_interval
 *
 * @property Expansion $expansion
 *
 * @mixin Eloquent
 */
class TimewalkingEvent extends CacheModel
{
    use SeederModel;
    use HasStart;

    public $timestamps = false;

//    REMOVE THIS CLASS? IS IT NEEDED? COUPLE IT TO A SEASON INSTEAD OF AN EXPANSION?

    const TIMEWALKING_EVENT_LEGION      = 'legion';
    const TIMEWALKING_EVENT_BFA         = 'bfa';
    const TIMEWALKING_EVENT_SHADOWLANDS = 'shadowlands';

    /**
     * @return BelongsTo
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    /**
     * @return BelongsTo
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
