<?php

namespace App\Models\Patreon;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id int
 * @property $patreon_user_link_id int
 * @property $patreon_benefit_id int
 *
 * @mixin Eloquent
 */
class PatreonUserBenefit extends Model
{
    protected $fillable = ['patreon_user_link_id', 'patreon_benefit_id'];

    /**
     * @return BelongsTo
     */
    function patreonuserlink(): BelongsTo
    {
        return $this->belongsTo(PatreonUserLink::class);
    }

    /**
     * @return BelongsTo
     */
    function patreonbenefit(): BelongsTo
    {
        return $this->belongsTo(PatreonBenefit::class);
    }
}
