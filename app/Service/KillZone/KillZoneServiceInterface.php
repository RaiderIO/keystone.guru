<?php

namespace App\Service\KillZone;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Service\KillZone\Exceptions\KillZoneDeleteRefusedException;
use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

interface KillZoneServiceInterface
{
    /**
     * Creates or updates a kill zone of the route from validated kill zone data (the shape of APIKillZoneFormRequest),
     * including its enemies and spells. Opens no transaction of its own: the caller wraps it in a (retried) one.
     *
     * @param array<string, mixed> $data
     *
     * @throws HttpException when the route's kill zone limit is reached, or a facade location belongs to no floor
     * @throws Exception
     */
    public function saveKillZone(DungeonRoute $dungeonRoute, array $data, bool $recalculateEnemyForces = true): KillZone;

    /**
     * Deletes a single kill zone of the route in its own transaction and returns the route's new enemy forces.
     *
     * @throws KillZoneDeleteRefusedException when the model refused the delete
     * @throws Exception                      when the kill zone does not exist (any more), or the delete failed
     */
    public function deleteKillZone(DungeonRoute $dungeonRoute, KillZone $killZone): int;
}
