<?php

namespace App\Service\Spell\Tuning\Dtos;

/**
 * Every spell of one game version as it was at one client build - the unit `spell:difftuning` compares.
 */
class SpellTuningSnapshot
{
    /**
     * @param array<int, SpellTuningSnapshotSpell> $spells keyed by spell id
     */
    public function __construct(
        public readonly string $build,
        public readonly int    $gameVersionId,
        public readonly array  $spells,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $spells decoded spells.json entries (or spell rows as arrays)
     */
    public static function fromSpellArrays(string $build, int $gameVersionId, array $spells): self
    {
        $result = [];
        foreach ($spells as $spell) {
            if ((int)($spell['game_version_id'] ?? 0) !== $gameVersionId) {
                continue;
            }

            $snapshotSpell              = SpellTuningSnapshotSpell::fromArray($spell);
            $result[$snapshotSpell->id] = $snapshotSpell;
        }

        return new self($build, $gameVersionId, $result);
    }
}
