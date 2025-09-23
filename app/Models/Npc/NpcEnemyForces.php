<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\SeederModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int      $id
 * @property int      $mapping_version_id
 * @property int      $npc_id
 * @property int      $enemy_forces
 * @property int|null $enemy_forces_teeming
 *
 * @property MappingVersion $mappingVersion
 * @property Npc            $npc
 *
 * @author Wouter
 *
 * @since 21/05/2023
 */
class NpcEnemyForces extends CacheModel implements MappingModelCloneableInterface, MappingModelInterface
{
    use CloneForNewMappingVersionNoRelations;
    use SeederModel;

    public $timestamps = false;

    public $fillable = [
        'id',
        'mapping_version_id',
        'npc_id',
        'enemy_forces',
        'enemy_forces_teeming',
    ];

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function getDungeonId(): ?int
    {
        return $this->npc->getDungeonId();
    }
}
