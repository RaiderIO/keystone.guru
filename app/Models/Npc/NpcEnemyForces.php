<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Models\Traits\SeederModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int            $id
 * @property int            $mapping_version_id
 * @property int            $npc_id
 * @property int            $enemy_forces
 * @property int|null       $enemy_forces_teeming
 *
 * @property MappingVersion $mappingVersion
 * @property Npc            $npc
 *
 * @package App\Models\Npc
 * @author Wouter
 * @since 21/05/2023
 */
class NpcEnemyForces extends CacheModel implements MappingModelInterface, MappingModelCloneableInterface
{
    use SeederModel;
    use CloneForNewMappingVersionNoRelations;

    public $timestamps = false;

    public $fillable = [
        'id',
        'mapping_version_id',
        'npc_id',
        'enemy_forces',
        'enemy_forces_teeming',
    ];

    /**
     * @return BelongsTo
     */
    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    /**
     * @return BelongsTo
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * @return int|null
     */
    public function getDungeonId(): ?int
    {
        return $this->npc->dungeon_id;
    }
}
