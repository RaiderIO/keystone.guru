<?php

namespace App\Service\MDT\Models;

use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

class ImportStringObjects
{
    private Collection $warnings;
    private Collection $errors;
    private Dungeon    $dungeon;
    private Collection $killZoneAttributes;
    private array      $mdtObjects;
    private Collection $lines;
    private Collection $paths;
    private Collection $mapIcons;

    /**
     * @param Collection $warnings
     * @param Collection $errors
     * @param Dungeon    $dungeon
     * @param Collection $killZoneAttributes
     * @param array      $mdtObjects
     */
    public function __construct(Collection $warnings, Collection $errors, Dungeon $dungeon, Collection $killZoneAttributes, array $mdtObjects)
    {
        $this->warnings           = $warnings;
        $this->errors             = $errors;
        $this->dungeon            = $dungeon;
        $this->killZoneAttributes = $killZoneAttributes;
        $this->mdtObjects         = $mdtObjects;

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
