<?php

namespace App\Models\Mapping;

use Illuminate\Database\Eloquent\Model;

trait CloneForNewMappingVersionNoRelations
{
    /**
     * @param MappingVersion $mappingVersion
     * @param MappingModelInterface|null $newParent
     * @return Model
     */
    public function cloneForNewMappingVersion(MappingVersion $mappingVersion, ?MappingModelInterface $newParent = null): Model
    {
        /** @var Model|MappingModelInterface $clone */
        $clone = clone $this;
        $clone->exists             = false;
        $clone->id                 = null;
        $clone->mapping_version_id = $mappingVersion->id;
        $clone->save();

        return $clone;
    }
}
