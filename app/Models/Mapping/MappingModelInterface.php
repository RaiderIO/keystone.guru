<?php

namespace App\Models\Mapping;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $mapping_version_id
 * @mixin Model
 */
interface MappingModelInterface
{
    public function getDungeonId(): int;
}
