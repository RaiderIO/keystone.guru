<?php

namespace App\Service\Wowhead\Dtos;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class NpcDataResult implements Arrayable
{
    /**
     * @param string     $classificationKey NpcClassification::NPC_CLASSIFICATION_*
     * @param int        $npcTypeId         NpcType::*
     * @param array<int> $zoneIds           Zone ids Wowhead places the NPC in, matching Dungeon::$zone_id
     */
    public function __construct(
        private readonly int    $npcId,
        private readonly string $name,
        private readonly ?int   $displayId,
        private readonly string $classificationKey,
        private readonly int    $npcTypeId,
        private readonly array  $zoneIds,
    ) {
    }

    public function getNpcId(): int
    {
        return $this->npcId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplayId(): ?int
    {
        return $this->displayId;
    }

    public function getClassificationKey(): string
    {
        return $this->classificationKey;
    }

    public function getNpcTypeId(): int
    {
        return $this->npcTypeId;
    }

    /**
     * @return array<int>
     */
    public function getZoneIds(): array
    {
        return $this->zoneIds;
    }

    /**
     * @return array{npc_id: int, name: string, display_id: int|null, classification_key: string, npc_type_id: int, zone_ids: array<int>}
     */
    public function toArray(): array
    {
        return [
            'npc_id'             => $this->npcId,
            'name'               => $this->name,
            'display_id'         => $this->displayId,
            'classification_key' => $this->classificationKey,
            'npc_type_id'        => $this->npcTypeId,
            'zone_ids'           => $this->zoneIds,
        ];
    }
}
