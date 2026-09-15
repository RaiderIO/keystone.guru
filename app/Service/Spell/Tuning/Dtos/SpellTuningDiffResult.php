<?php

namespace App\Service\Spell\Tuning\Dtos;

use App\Models\Spell\SpellTuningChangeType;
use Illuminate\Support\Carbon;

/**
 * What `SpellTuningDiffService::diff()` found between two snapshots.
 */
class SpellTuningDiffResult
{
    /**
     * @param array<int, SpellTuningChangeDto> $changes
     * @param int                              $comparedSpellCount spells present in both snapshots
     */
    public function __construct(
        public readonly string $fromBuild,
        public readonly string $toBuild,
        public readonly int    $gameVersionId,
        public readonly array  $changes,
        public readonly int    $comparedSpellCount,
    ) {
    }

    public function getChangedSpellCount(): int
    {
        return count(array_unique(array_map(
            static fn(SpellTuningChangeDto $change): int => $change->spellId,
            $this->changes,
        )));
    }

    public function getRewrittenCount(): int
    {
        return count(array_filter(
            $this->changes,
            static fn(SpellTuningChangeDto $change): bool => $change->changeType === SpellTuningChangeType::DescriptionRewritten,
        ));
    }

    /**
     * The rows to store for this result.
     *
     * @param  Carbon|null                      $toBuildReleasedAt when the target build went live, if known
     * @return array<int, array<string, mixed>>
     */
    public function toRows(?Carbon $toBuildReleasedAt = null): array
    {
        return array_map(
            fn(SpellTuningChangeDto $change): array => $change->toRow($this->gameVersionId, $this->fromBuild, $this->toBuild, $toBuildReleasedAt),
            $this->changes,
        );
    }
}
