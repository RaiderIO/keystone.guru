<?php

namespace App\Logic\SimulationCraft;

/**
 * The whole party being forced to move a distance.
 */
class RaidEventPullMovement extends RaidEventPullChildEvent
{
    /**
     * The yards per second SimulationCraft's movement event assumes for every player.
     */
    private const float SIMULATION_CRAFT_MOVEMENT_SPEED = 7.0;

    public function __construct(
        int                    $pullIndex,
        RaidEventPullTiming    $timing,
        private readonly float $distance,
    ) {
        parent::__construct($pullIndex, $timing);
    }

    protected function getType(): string
    {
        return 'movement';
    }

    protected function getEventOptions(): array
    {
        $options = [sprintf('distance=%s', RaidEventPullTiming::formatNumber($this->distance))];

        // SimulationCraft caps the distance at what can be walked within the cooldown, even when timestamps= schedules
        // the event and the cooldown is never used. Without one the cap is zero and nobody moves.
        if ($this->timing->isOnce()) {
            $options[] = sprintf(
                'cooldown=%s',
                RaidEventPullTiming::formatNumber(ceil($this->distance / self::SIMULATION_CRAFT_MOVEMENT_SPEED * 10) / 10),
            );
        }

        return $options;
    }
}
