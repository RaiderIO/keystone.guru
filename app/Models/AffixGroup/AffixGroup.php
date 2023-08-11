<?php

namespace App\Models\AffixGroup;

use App\Models\Expansion;
use App\Models\Season;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $season_id
 * @property int $expansion_id
 * @property int|null $seasonal_index
 * @property bool $confirmed
 *
 * @property Season $season
 * @property Expansion $expansion
 * @property Collection|AffixGroupEaseTier[] $easetiers
 *
 * @mixin Eloquent
 */
class AffixGroup extends AffixGroupBase
{
    public $fillable = ['season_id', 'seasonal_index', 'confirmed'];


    protected function getAffixGroupCouplingsTableName(): string
    {
        return 'affix_group_couplings';
    }

    /**
     * @return BelongsTo
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * @return BelongsTo
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo(Expansion::class);
    }

    /**
     * @return HasMany
     */
    public function easetiers(): HasMany
    {
        return $this->hasMany(AffixGroupEaseTier::class);
    }

    /**
     * @param Season     $season
     * @param Collection $affixIds
     * @return void
     */
    public static function findMatchingAffixGroupsForAffixIds(Season $season, Collection $affixIds): Collection
    {
        $result = collect();

        $eligibleAffixGroups = AffixGroup::where('season_id', $season->id)->get();
        foreach ($eligibleAffixGroups as $eligibleAffixGroup) {
            // If the affix group's affixes are all in $affixIds
            if ($affixIds->diff($eligibleAffixGroup->affixes->pluck('affix_id'))->isEmpty()) {
                // Couple the affix group to the newly created dungeon route
                $result->push($eligibleAffixGroup);
            }
        }

        return $result;
    }
}
