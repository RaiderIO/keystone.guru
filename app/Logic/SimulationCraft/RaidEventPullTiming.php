<?php

namespace App\Logic\SimulationCraft;

/**
 * When a child raid event of a pull happens, relative to the pull's start: from a time or from the health of an
 * enemy of the pull, then every cooldown - or once, when there is no cooldown.
 */
readonly class RaidEventPullTiming
{
    private function __construct(
        private ?int    $firstMs,
        private ?float  $firstHealthPct,
        private ?string $pullTarget,
        private ?int    $cooldownMs,
    ) {
    }

    public static function atTime(int $firstMs, ?int $cooldownMs): self
    {
        return new self($firstMs, null, null, $cooldownMs);
    }

    /**
     * SimulationCraft cannot say "once" for a health trigger - timestamps= excludes first_pct=, and an event without
     * timestamps needs a cooldown - so a health-triggered event always repeats.
     *
     * @param string $pullTarget The name of the enemy in the pull whose health triggers the event
     */
    public static function atHealth(float $firstHealthPct, string $pullTarget, int $cooldownMs): self
    {
        return new self(null, $firstHealthPct, $pullTarget, $cooldownMs);
    }

    public function isOnce(): bool
    {
        return $this->cooldownMs === null;
    }

    public function getCooldownMs(): ?int
    {
        return $this->cooldownMs;
    }

    /**
     * @return list<string>
     */
    public function getOptions(): array
    {
        if ($this->firstHealthPct !== null) {
            return [
                sprintf('first_pct=%s', self::formatNumber($this->firstHealthPct)),
                sprintf('cooldown=%s', self::formatSeconds((int)$this->cooldownMs)),
                sprintf('pull_target=%s', $this->pullTarget),
            ];
        }

        // A single timestamp is SimulationCraft's way to run an event exactly once
        if ($this->cooldownMs === null) {
            return [sprintf('timestamps=%s', self::formatSeconds((int)$this->firstMs))];
        }

        return [
            sprintf('first=%s', self::formatSeconds((int)$this->firstMs)),
            sprintf('cooldown=%s', self::formatSeconds((int)$this->cooldownMs)),
        ];
    }

    public static function formatSeconds(int $ms): string
    {
        return self::formatNumber($ms / 1000);
    }

    public static function formatNumber(float $value): string
    {
        return sprintf('%.1F', $value);
    }
}
