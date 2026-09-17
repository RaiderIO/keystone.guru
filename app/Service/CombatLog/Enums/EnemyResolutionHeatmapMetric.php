<?php

namespace App\Service\CombatLog\Enums;

/**
 * What a grid cell's weight says about the matches recorded in it.
 */
enum EnemyResolutionHeatmapMetric: string
{
    /** The average distance of the cell's matches - a spot only reads hot when it is consistently off, not once */
    case Average = 'average';

    /** The worst single match in the cell - finds the one enemy that resolved from across the room */
    case Max = 'max';

    public function label(): string
    {
        return match ($this) {
            self::Average => __('view_common.maps.controls.combatlogrouteenemyresolutions.metric_average'),
            self::Max     => __('view_common.maps.controls.combatlogrouteenemyresolutions.metric_max'),
        };
    }
}
