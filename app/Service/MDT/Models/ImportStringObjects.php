<?php

namespace App\Service\MDT\Models;

use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

class ImportStringObjects
{
    private readonly Collection $lines;
    private readonly Collection $paths;
    private readonly Collection $mapIcons;

    public function __construct(private readonly Collection $warnings, private readonly Collection $errors, private readonly Dungeon $dungeon, private readonly Collection $killZoneAttributes, private readonly array $mdtObjects)
    {
        $this->lines    = collect();
        $this->paths    = collect();
        $this->mapIcons = collect();
    }

    /**
     * @return Collection|ImportWarning[]
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    /**
     * @return Collection|ImportError[]
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    /**
     * @return Dungeon
     */
    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    /**
     * @return Collection
     */
    public function getKillZoneAttributes(): Collection
    {
        return $this->killZoneAttributes;
    }

    /**
     * @return array
     */
    public function getMdtObjects(): array
    {
        return $this->mdtObjects;
    }

    /**
     * @return Collection
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    /**
     * @return Collection
     */
    public function getPaths(): Collection
    {
        return $this->paths;
    }

    /**
     * @return Collection
     */
    public function getMapIcons(): Collection
    {
        return $this->mapIcons;
    }
}
