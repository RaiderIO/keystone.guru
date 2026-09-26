<?php

namespace App\Service\Spell\Tuning;

use App\Service\Spell\Tuning\Dtos\SpellTuningDiffResult;
use App\Service\Spell\Tuning\Dtos\SpellTuningSnapshot;
use Illuminate\Support\Carbon;

interface SpellTuningDiffServiceInterface
{
    /**
     * Finds every spell whose description numbers differ between two snapshots of the same game version.
     *
     * Only the numbers are compared - a sentence that was reworded around the same values is not a
     * change. Spells present in only one snapshot are not changes either.
     */
    public function diff(SpellTuningSnapshot $from, SpellTuningSnapshot $to): SpellTuningDiffResult;

    /**
     * Stores a result, replacing whatever was recorded for its target build before so re-running the
     * diff for the same build pair is idempotent. The build itself is recorded as compared even when the
     * result holds no changes. Returns the number of change rows stored.
     *
     * @param Carbon|null $toBuildReleasedAt when the target build went live; null keeps whatever date is already
     *                                       recorded for the build
     */
    public function store(SpellTuningDiffResult $result, ?Carbon $toBuildReleasedAt = null): int;
}
