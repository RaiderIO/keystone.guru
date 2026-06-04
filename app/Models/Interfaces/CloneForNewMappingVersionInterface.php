<?php

namespace App\Models\Interfaces;

use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Eloquent\Model;

interface CloneForNewMappingVersionInterface
{
    public function cloneForNewMappingVersion(MappingVersion $mappingVersion, ?MappingModelInterface $newParent = null): Model;
}
