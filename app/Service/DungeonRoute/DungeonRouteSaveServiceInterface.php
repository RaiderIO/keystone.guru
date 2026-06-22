<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;

interface DungeonRouteSaveServiceInterface
{
    /**
     * @param array<string, mixed> $validated
     */
    public function save(DungeonRoute $dungeonRoute, array $validated): bool;

    /**
     * @param array<string, mixed> $validated
     */
    public function saveTemporary(DungeonRoute $dungeonRoute, array $validated): bool;

    public function cloneRoute(DungeonRoute $source, bool $unpublished = true): DungeonRoute;
}
