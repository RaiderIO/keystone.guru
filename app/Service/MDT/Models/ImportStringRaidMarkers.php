<?php

namespace App\Service\MDT\Models;

use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

class ImportStringRaidMarkers
{
    /**
     * @var Collection<int, array{npc_id: int, mdt_id: int|null, enemy_id: int, raid_marker_id: int}>
     */
    private readonly Collection $raidMarkerAttributes;

    /**
     * @param Collection<int, ImportWarning> $warnings
     * @param array<int|string, mixed>       $mdtEnemyAssignments
     */
    public function __construct(
        private readonly Collection     $warnings,
        private readonly Dungeon        $dungeon,
        private readonly MappingVersion $mappingVersion,
        private readonly array          $mdtEnemyAssignments,
    ) {
        $this->raidMarkerAttributes = collect();
    }

    /**
     * @return Collection<int, ImportWarning>
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function getMappingVersion(): MappingVersion
    {
        return $this->mappingVersion;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getMdtEnemyAssignments(): array
    {
        return $this->mdtEnemyAssignments;
    }

    /**
     * @param array{npc_id: int, mdt_id: int|null, enemy_id: int, raid_marker_id: int} $attributes
     */
    public function addRaidMarkerAttributes(array $attributes): self
    {
        $this->raidMarkerAttributes->push($attributes);

        return $this;
    }

    /**
     * @return Collection<int, array{npc_id: int, mdt_id: int|null, enemy_id: int, raid_marker_id: int}>
     */
    public function getRaidMarkerAttributes(): Collection
    {
        return $this->raidMarkerAttributes;
    }
}
