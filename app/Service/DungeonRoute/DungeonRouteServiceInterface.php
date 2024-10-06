<?php

namespace App\Service\DungeonRoute;

interface DungeonRouteServiceInterface
{
    public function updatePopularity(): int;

    public function updateRating(): int;

    public function refreshOutdatedThumbnails(): int;

    public function deleteOutdatedDungeonRoutes(): int;
}
