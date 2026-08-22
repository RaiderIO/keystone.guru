<?php

namespace App\Service\Spell\Tuning;

use App\Models\Spell\SpellTuningChangeType;
use App\Repositories\Interfaces\Spell\SpellTuningChangeRepositoryInterface;
use App\Service\Spell\Description\Dtos\SpellDescriptionValue;
use App\Service\Spell\Tuning\Dtos\SpellTuningChangeDto;
use App\Service\Spell\Tuning\Dtos\SpellTuningDiffResult;
use App\Service\Spell\Tuning\Dtos\SpellTuningSnapshot;
use App\Service\Spell\Tuning\Dtos\SpellTuningSnapshotSpell;
use App\Service\Spell\Tuning\Logging\SpellTuningDiffServiceLoggingInterface;
use InvalidArgumentException;

class SpellTuningDiffService implements SpellTuningDiffServiceInterface
{
    /**
     * Coefficients are floats straight out of the client data and can differ in their last digits between
     * builds without anything having changed (105.79386901856 vs 105.79386901855 was observed). Anything
     * closer than this, relative to the larger of the two, is the same number.
     */
    public const float RELATIVE_EPSILON = 1e-6;

    public function __construct(
        private readonly SpellTuningChangeRepositoryInterface   $spellTuningChangeRepository,
        private readonly SpellTuningDiffServiceLoggingInterface $log,
    ) {
    }

    public function diff(SpellTuningSnapshot $from, SpellTuningSnapshot $to): SpellTuningDiffResult
    {
        if ($from->gameVersionId !== $to->gameVersionId) {
            throw new InvalidArgumentException(sprintf(
                'Cannot diff snapshots of different game versions (%d vs %d).',
                $from->gameVersionId,
                $to->gameVersionId,
            ));
        }

        $this->log->diffStart($from->build, $to->build, $from->gameVersionId);

        try {
            $changes  = [];
            $compared = 0;

            foreach ($to->spells as $spellId => $newSpell) {
                $oldSpell = $from->spells[$spellId] ?? null;
                if ($oldSpell === null) {
                    continue;
                }

                $compared++;

                $spellChanges = $this->diffSpell($oldSpell, $newSpell);
                if ($spellChanges !== []) {
                    $this->log->diffSpellChanged($spellId, count($spellChanges));
                    array_push($changes, ...$spellChanges);
                }
            }

            return new SpellTuningDiffResult($from->build, $to->build, $from->gameVersionId, $changes, $compared);
        } finally {
            $this->log->diffEnd();
        }
    }

    public function store(SpellTuningDiffResult $result): int
    {
        $this->log->storeStart($result->fromBuild, $result->toBuild, $result->gameVersionId, count($result->changes));

        try {
            return $this->spellTuningChangeRepository->replaceForBuild(
                $result->gameVersionId,
                $result->toBuild,
                $result->toRows(),
            );
        } finally {
            $this->log->storeEnd();
        }
    }

    /**
     * @return array<int, SpellTuningChangeDto>
     */
    private function diffSpell(SpellTuningSnapshotSpell $old, SpellTuningSnapshotSpell $new): array
    {
        // Neither build describes the spell - nothing to compare
        if (!$old->hasDescription() && !$new->hasDescription()) {
            return [];
        }

        // A description appearing, disappearing, or changing which numbers it has (in count or kind)
        // cannot be paired value by value; the whole text is recorded instead
        if ($old->hasDescription() !== $new->hasDescription() || $old->getKindSequence() !== $new->getKindSequence()) {
            return [
                new SpellTuningChangeDto(
                    spellId: $new->id,
                    changeType: SpellTuningChangeType::DescriptionRewritten,
                    valueIndex: null,
                    kind: null,
                    oldCoefficient: null,
                    newCoefficient: null,
                    oldText: $old->render(),
                    newText: $new->render(),
                    delta: null,
                ),
            ];
        }

        $changes = [];
        foreach ($new->values as $index => $newValue) {
            $oldValue = $old->values[$index];

            if ($newValue->kind->isScalable()) {
                $change = $this->diffScalableValue($new->id, $index, $oldValue, $newValue);
            } else {
                $change = $this->diffPlainValue($new->id, $index, $oldValue, $newValue);
            }

            if ($change !== null) {
                $changes[] = $change;
            }
        }

        return $changes;
    }

    /**
     * Damage and healing are compared on their coefficient: the rendered number is that coefficient times
     * the spell's damage multiplier, which is measured rather than shipped by the client, so a multiplier
     * being measured for the first time would otherwise read as a tuning change. The multiplier cancels
     * out of the delta entirely.
     */
    private function diffScalableValue(int $spellId, int $index, SpellDescriptionValue $old, SpellDescriptionValue $new): ?SpellTuningChangeDto
    {
        if (self::isSameNumber($old->coefficient, $new->coefficient)) {
            return null;
        }

        $delta = null;
        if ($old->coefficient !== null && $new->coefficient !== null && $old->coefficient != 0.0) {
            $delta = $new->coefficient / $old->coefficient - 1;
        }

        return new SpellTuningChangeDto(
            spellId: $spellId,
            changeType: SpellTuningChangeType::ValueChanged,
            valueIndex: $index,
            kind: $new->kind,
            oldCoefficient: $old->coefficient,
            newCoefficient: $new->coefficient,
            oldText: $old->text,
            newText: $new->text,
            delta: $delta,
        );
    }

    /**
     * Every other kind of number (a duration, a radius, a percentage) is the same at every key level and
     * carries no coefficient, so its rendered text is what there is to compare.
     */
    private function diffPlainValue(int $spellId, int $index, SpellDescriptionValue $old, SpellDescriptionValue $new): ?SpellTuningChangeDto
    {
        if ($old->text === $new->text) {
            return null;
        }

        return new SpellTuningChangeDto(
            spellId: $spellId,
            changeType: SpellTuningChangeType::ValueChanged,
            valueIndex: $index,
            kind: $new->kind,
            oldCoefficient: null,
            newCoefficient: null,
            oldText: $old->text,
            newText: $new->text,
            delta: null,
        );
    }

    public static function isSameNumber(?float $a, ?float $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return abs($a - $b) <= self::RELATIVE_EPSILON * max(abs($a), abs($b));
    }
}
