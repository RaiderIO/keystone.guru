<?php

namespace App\Service\DungeonRoute;

interface ThumbnailGenerationToggleServiceInterface
{
    public function setPaused(bool $paused): bool;

    public function isPaused(): bool;
}
