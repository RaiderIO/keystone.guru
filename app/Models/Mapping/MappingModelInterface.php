<?php

namespace App\Models\Mapping;

use App\Models\Floor;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $floor_id
 * @property int $mapping_version_id
 *
 * @property Floor $floor
 * @property MappingVersion $mappingVersion
 *
 * @mixin Model
 */
interface MappingModelInterface
{
    public function getDungeonId(): ?int;
}
