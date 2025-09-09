<?php

namespace App\Models\Patreon;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $patreon_user_link_id
 * @property int $patreon_benefit_id
 *
 * @mixin Eloquent
 */
class PatreonUserBenefit extends Model
{
    protected $fillable = [
        'patreon_user_link_id',
        'patreon_benefit_id',
    ];

    public function patreonuserlink(): BelongsTo
    {
        return $this->belongsTo(PatreonUserLink::class);
    }

    public function patreonbenefit(): BelongsTo
    {
        return $this->belongsTo(PatreonBenefit::class);
    }
}
