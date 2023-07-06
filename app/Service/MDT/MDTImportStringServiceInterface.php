<?php

namespace App\Service\MDT;

use App\Models\DungeonRoute;
use App\Service\MDT\Models\ImportStringDetails;
use Illuminate\Support\Collection;

interface MDTImportStringServiceInterface
{
    public function getDecoded(): ?array;

    public function getDetails(Collection $warnings): ImportStringDetails;

    public function getDungeonRoute(Collection $warnings, bool $sandbox = false, bool $save = false): DungeonRoute;

    public function setEncodedString(string $encodedString): self;
}
