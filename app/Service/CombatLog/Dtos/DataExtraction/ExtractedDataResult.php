<?php

namespace App\Service\CombatLog\Dtos\DataExtraction;

use Illuminate\Contracts\Support\Arrayable;

class ExtractedDataResult implements Arrayable
{
    private int $createdNpcs             = 0;
    private int $createdNpcSpells        = 0;
    private int $updatedNpcs             = 0;
    private int $updatedFloors           = 0;
    private int $updatedFloorConnections = 0;
    private int $createdSpells           = 0;
    private int $createdSpellDungeon     = 0;
    private int $updatedSpells           = 0;

    public function updatedNpc(): void
    {
        $this->updatedNpcs++;
    }

    public function createdNpc(): void
    {
        $this->createdNpcs++;
    }

    public function createdNpcSpell(): void
    {
        $this->createdNpcSpells++;
    }

    public function updatedFloor(): void
    {
        $this->updatedFloors++;
    }

    public function updatedFloorConnection(): void
    {
        $this->updatedFloorConnections++;
    }

    public function createdSpell(): void
    {
        $this->createdSpells++;
    }

    public function createdSpellDungeon(): void
    {
        $this->createdSpellDungeon++;
    }

    public function updatedSpell(): void
    {
        $this->updatedSpells++;
    }

    public function toArray(): array
    {
        return [
            'createdNpcs'             => $this->createdNpcs,
            'createdNpcSpells'        => $this->createdNpcSpells,
            'updatedNpcs'             => $this->updatedNpcs,
            'updatedFloors'           => $this->updatedFloors,
            'updatedFloorConnections' => $this->updatedFloorConnections,
            'createdSpells'           => $this->createdSpells,
            'createdSpellDungeon'     => $this->createdSpellDungeon,
            'updatedSpells'           => $this->updatedSpells,
        ];
    }
}
