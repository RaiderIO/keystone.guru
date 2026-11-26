<?php

namespace App\Logic\SimulationCraft;

/**
 * An enemy of the pull that takes no damage for a while; players switch to another target meanwhile.
 */
class RaidEventPullInvulnerable extends RaidEventPullChildEvent
{
    /**
     * @param string $target The name of the enemy in the pull, without SimulationCraft's Pull_<n>_ prefix
     */
    public function __construct(
        int                     $pullIndex,
        RaidEventPullTiming     $timing,
        private readonly string $target,
        private readonly int    $durationMs,
    ) {
        parent::__construct($pullIndex, $timing);
    }

    protected function getType(): string
    {
        return 'invulnerable';
    }

    protected function getLeadingOptions(): array
    {
        return [sprintf('target=%s', $this->target)];
    }

    protected function getEventOptions(): array
    {
        // No force_stop: the pull cannot end while its target is invulnerable anyway, and SimulationCraft's force_stop
        // finishes a never-started event at the pull's end against the sim's main target, the only one it knows then
        return [
            sprintf('duration=%s', RaidEventPullTiming::formatSeconds($this->durationMs)),
            'retarget=1',
        ];
    }
}
