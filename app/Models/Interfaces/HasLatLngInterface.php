<?php

namespace App\Models\Interfaces;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Service\Coordinates\CoordinatesServiceInterface;

/**
 * @property float|null $lat
 * @property float|null $lng
 * @property Floor|null $floor
 */
interface HasLatLngInterface
{
    public function hasValidLatLng(): bool;

    public function getLatLng(): LatLng;

    public function setLatLng(LatLng $latLng): self;

    /** @return array<string, mixed> */
    public function getCoordinatesData(CoordinatesServiceInterface $coordinatesService): array;
}
