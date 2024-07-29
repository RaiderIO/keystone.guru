<?php

namespace App\Service\CombatLog\Dtos\DataExtraction;

class ExtractedDataResult
{
    private int $createdNpcs             = 0;
    private int $updatedNpcs             = 0;
    private int $updatedFloors           = 0;
    private int $updatedFloorConnections = 0;

    public function getCreatedNpcs(): int
    {
        return $this->createdNpcs;
    }

    public function getUpdatedNpcs(): int
    {
        return $this->updatedNpcs;
    }

    public function updatedNpc(): void
    {
        $this->updatedNpcs++;
    }

    public function getUpdatedFloors(): int
    {
        return $this->updatedFloors;
    }

    public function createdNpc(): void
    {
        $this->createdNpcs++;
    }

    public function updatedFloor(): void
    {
        $this->updatedFloors++;
    }

    public function getUpdatedFloorConnections(): int
    {
        return $this->updatedFloorConnections;
    }

    public function updatedFloorConnection(): void
    {
        $this->updatedFloorConnections++;
    }

    public function hasUpdatedData(): bool
    {
        return $this->createdNpcs || $this->updatedFloors || $this->updatedFloorConnections || $this->updatedNpcs;
    }
}
