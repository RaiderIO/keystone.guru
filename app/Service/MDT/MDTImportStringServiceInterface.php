<?php

namespace App\Service\MDT;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\MDT\Models\ImportStringDetails;
use Illuminate\Support\Collection;

interface MDTImportStringServiceInterface
{
    public function getDecoded(): ?array;

    public function getDetails(Collection $warnings, Collection $errors): ImportStringDetails;

    public function getDungeonRoute(Collection $warnings, Collection $errors, bool $sandbox = false, bool $save = false, bool $importAsThisWeek = false): DungeonRoute;

    public function setEncodedString(string $encodedString): self;
}
