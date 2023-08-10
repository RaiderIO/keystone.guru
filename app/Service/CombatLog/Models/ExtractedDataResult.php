<?php

namespace App\Service\CombatLog\Models;

class ExtractedDataResult
{
    private int $updatedNpcs = 0;

    private int $updatedFloors = 0;

    private int $updatedFloorConnections = 0;

    /**
     * @return int
     */
    public function getUpdatedNpcs(): int
    {
        return $this->updatedNpcs;
    }

    /**
     * @return void
     */
    public function updatedNpc(): void
    {
        $this->updatedNpcs++;
    }

    /**
     * @return int
     */
    public function getUpdatedFloors(): int
    {
        return $this->updatedFloors;
    }

    /**
     * @return void
     */
    public function updatedFloor(): void
    {
        $this->updatedFloors++;
    }

    /**
     * @return int
     */
    public function getUpdatedFloorConnections(): int
    {
        return $this->updatedFloorConnections;
    }

    /**
     * @return void
     */
    public function updatedFloorConnection(): void
    {
        $this->updatedFloorConnections++;
    }

    /**
     * @return bool
     */
    public function hasUpdatedData(): bool
    {
        return $this->updatedFloors || $this->updatedFloorConnections || $this->updatedNpcs;
    }
}
