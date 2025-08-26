<?php

namespace App\Service\MDT;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface MDTExportStringServiceInterface
{
    public function getEncodedString(Collection $warnings, bool $useCache = true): string;

    public function setDungeonRoute(DungeonRoute $dungeonRoute): self;
}
