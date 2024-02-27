<?php

namespace App\Models\AffixGroup;

use App\Models\CacheModel;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $affix_group_id
 * @property string $tiers_hash
 * @property Carbon $last_updated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property AffixGroup $affixGroup
 * @property Collection|AffixGroupEaseTier[] $affixGroupEaseTiers
 *
 * @mixin Eloquent
 **/
class AffixGroupEaseTierPull extends CacheModel
{
    protected $with = ['affixGroup', 'affixGroupEaseTiers'];

    protected $fillable = [
        'affix_group_id',
        'tiers_hash',
        'last_updated_at',
    ];

    public function affixGroup(): BelongsTo
    {
        return $this->belongsTo(AffixGroup::class);
    }

    public function affixGroupEaseTiers(): HasMany
    {
        return $this->hasMany(AffixGroupEaseTier::class);
    }

    /**
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Delete AffixGroupEaseTiers properly if it gets deleted
        static::deleting(static function (AffixGroupEaseTierPull $affixGroupEaseTierPull) {
            $affixGroupEaseTierPull->affixGroupEaseTiers()->delete();
        });
    }
}
