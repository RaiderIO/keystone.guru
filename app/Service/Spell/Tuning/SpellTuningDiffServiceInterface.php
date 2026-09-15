<?php

namespace App\Service\Spell\Tuning;

use App\Service\Spell\Tuning\Dtos\SpellTuningDiffResult;
use App\Service\Spell\Tuning\Dtos\SpellTuningSnapshot;

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
     * diff for the same build pair is idempotent. Returns the number of rows stored.
     */
    public function store(SpellTuningDiffResult $result): int;
}
