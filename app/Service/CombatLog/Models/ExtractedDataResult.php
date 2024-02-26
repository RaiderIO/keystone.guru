<?php

namespace App\Service\CombatLog\Models;

class ExtractedDataResult
{
    private int $updatedNpcs = 0;

    private int $updatedFloors = 0;

    private int $updatedFloorConnections = 0;

    public function getUpdatedNpcs(): int
    {
        return $this->updatedNpcs;
    }

    public function updatedNpc(): void
    {
        ++$this->updatedNpcs;
    }

    public function getUpdatedFloors(): int
    {
        return $this->updatedFloors;
    }

    public function updatedFloor(): void
    {
        ++$this->updatedFloors;
    }

    public function getUpdatedFloorConnections(): int
    {
        return $this->updatedFloorConnections;
    }

    public function updatedFloorConnection(): void
    {
        ++$this->updatedFloorConnections;
    }

    public function hasUpdatedData(): bool
    {
        return $this->updatedFloors || $this->updatedFloorConnections || $this->updatedNpcs;
    }
}
