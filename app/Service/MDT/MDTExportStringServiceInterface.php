<?php

namespace App\Service\MDT;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface MDTExportStringServiceInterface
{
    public function getEncodedString(Collection $warnings): string;

    public function setDungeonRoute(DungeonRoute $dungeonRoute): self;
}
