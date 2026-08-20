<?php

namespace App\Service\CombatLog\Dtos\DataExtraction;

/**
 * Every max-HP value one NPC was seen with inside one challenge mode, with how often each value was seen.
 *
 * Advanced combat log lines carry the unit's max HP on every event, so one NPC yields hundreds of samples per
 * run - nearly all identical, with the odd deviation when a buff raises its max HP (Bolstering, shields that
 * report as health). The most observed value is therefore the unbuffed one, which is what the reversal wants.
 */
class NpcHealthObservation
{
    /** @var array<int, int> maxHp => number of lines it was observed on */
    private array $samples = [];

    /**
     * @param array<int, string> $affixes Affix:: key constants active during the challenge mode
     */
    public function __construct(
        public readonly int   $npcId,
        public readonly int   $dungeonId,
        public readonly int   $keyLevel,
        public readonly array $affixes,
    ) {
    }

    public function addSample(int $maxHp): void
    {
        $this->samples[$maxHp] = ($this->samples[$maxHp] ?? 0) + 1;
    }

    /**
     * @return array<int, int>
     */
    public function getSamples(): array
    {
        return $this->samples;
    }

    public function getSampleCount(): int
    {
        return array_sum($this->samples);
    }

    /**
     * The max HP seen on most lines; on a tie the lowest wins, since buffs only ever raise max HP.
     */
    public function getMostObservedMaxHp(): int
    {
        $result      = 0;
        $resultCount = 0;

        foreach ($this->samples as $maxHp => $count) {
            if ($count > $resultCount || ($count === $resultCount && $maxHp < $result)) {
                $result      = $maxHp;
                $resultCount = $count;
            }
        }

        return $result;
    }
}
