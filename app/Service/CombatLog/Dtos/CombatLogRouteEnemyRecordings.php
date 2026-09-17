<?php

namespace App\Service\CombatLog\Dtos;

/**
 * The diagnostic rows one combat log route produces about how its npcs were matched to mapped enemies: the ones that
 * matched nothing, and the ones that matched something suspiciously far away.
 */
readonly class CombatLogRouteEnemyRecordings
{
    /**
     * @param array<int, array<string, mixed>> $failures    combat_log_route_enemy_failures rows
     * @param array<int, array<string, mixed>> $resolutions combat_log_route_enemy_resolutions rows
     */
    public function __construct(
        public array $failures = [],
        public array $resolutions = [],
    ) {
    }
}
