<?php

namespace App\Models\Mapping;

use Illuminate\Database\Eloquent\Model;

interface MappingModelCloneableInterface
{
    public function cloneForNewMappingVersion(
        MappingVersion         $mappingVersion,
        ?MappingModelInterface $newParent = null,
    ): Model;
}
