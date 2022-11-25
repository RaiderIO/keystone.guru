<?php

namespace App\Service\MDT;

use App\Models\DungeonRoute;
use Illuminate\Support\Collection;

interface MDTImportStringServiceInterface
{
    public function getDecoded(): ?array;

    public function getDungeonRoute(Collection $warnings, bool $sandbox = false, bool $save = false): DungeonRoute;

    public function setEncodedString(string $encodedString): self;
}
