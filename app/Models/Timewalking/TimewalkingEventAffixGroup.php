<?php

namespace App\Models\Timewalking;

use App;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroupBase;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int The ID of this Affix.
 * @property $timewalking_event_id int
 * @property $seasonal_index int
 * @property $seasonal_index_in_season int Only set in rare case - not a database column! See KeystoneGuruServiceProvider.php
 * @property $text string To string of the affix group
 *
 * @property Collection|Affix[] $affixes
 *
 * @mixin Eloquent
 */
class TimewalkingEventAffixGroup extends AffixGroupBase
{
    public $fillable = ['timewalking_event_id', 'seasonal_index'];

    protected function getAffixGroupCouplingsTableName(): string
    {
        return 'timewalking_event_affix_group_couplings';
    }

    /**
     * @return BelongsTo
     */
    public function timewalkingevent(): BelongsTo
    {
        return $this->belongsTo('App\Models\Timewalking\TimewalkingEvent');
    }
}
