<?php

namespace App\Service\MDT;

use App\Logic\MDT\Exception\ImportWarning;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface MDTExportStringServiceInterface
{
    /**
     * @param Collection<int, ImportWarning> $warnings
     */
    public function getEncodedString(Collection $warnings, bool $useCache = true): string;

    public function setDungeonRoute(DungeonRoute $dungeonRoute): self;
}
