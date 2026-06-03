<?php

namespace App\Models\Mapping;

use Illuminate\Database\Eloquent\Model;


trait CloneForNewMappingVersionNoRelations
{
    public function cloneForNewMappingVersion(
        MappingVersion         $mappingVersion,
        ?MappingModelInterface $newParent = null,
    ): Model {
        /** @var static&Model&MappingModelInterface $clone */
        $clone         = clone $this;
        $clone->exists = false;
        unset($clone->id);
        $clone->mapping_version_id = $mappingVersion->id;
        $clone->save();

        return $clone;
    }
}
