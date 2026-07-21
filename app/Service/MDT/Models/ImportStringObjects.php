<?php

namespace App\Service\MDT\Models;

use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

class ImportStringObjects
{
    /**
     * @var Collection<int, mixed>
     */
    private readonly Collection $lines;

    /**
     * @var Collection<int, mixed>
     */
    private readonly Collection $paths;

    /**
     * @var Collection<int, mixed>
     */
    private readonly Collection $arrows;

    /**
     * @var Collection<int, mixed>
     */
    private readonly Collection $mapIcons;

    /**
     * @param Collection<int, ImportWarning> $warnings
     * @param Collection<int, ImportError>   $errors
     * @param Collection<int, mixed>         $killZoneAttributes
     * @param array<int, mixed>              $mdtObjects
     */
    public function __construct(
        private readonly Collection     $warnings,
        private readonly Collection     $errors,
        private readonly Dungeon        $dungeon,
        private readonly MappingVersion $mappingVersion,
        private readonly Collection     $killZoneAttributes,
        private readonly array          $mdtObjects,
    ) {
        $this->lines    = collect();
        $this->paths    = collect();
        $this->arrows   = collect();
        $this->mapIcons = collect();
    }

    /**
     * @return Collection<int, ImportWarning>
     */
    public function getWarnings(): Collection
    {
        return $this->warnings;
    }

    /**
     * @return Collection<int, ImportError>
     */
    public function getErrors(): Collection
    {
        return $this->errors;
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
     * @return Collection<int, mixed>
     */
    public function getKillZoneAttributes(): Collection
    {
        return $this->killZoneAttributes;
    }

    /**
     * @return array<int, mixed>
     */
    public function getMdtObjects(): array
    {
        return $this->mdtObjects;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getPaths(): Collection
    {
        return $this->paths;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getArrows(): Collection
    {
        return $this->arrows;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getMapIcons(): Collection
    {
        return $this->mapIcons;
    }
}
