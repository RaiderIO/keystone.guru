<?php

use App\Models\KillZone\KillZone;
use App\Service\Coordinates\CoordinatesService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $coordinatesService = new CoordinatesService();

        KillZone::query()
            ->join('floors', 'floors.id', '=', 'kill_zones.floor_id')
            ->where('floors.facade', true)
            ->whereNotNull('kill_zones.lat')
            ->whereNotNull('kill_zones.lng')
            ->with(['floor', 'dungeonRoute.mappingVersion'])
            ->select('kill_zones.*')
            ->each(function (KillZone $killZone) use ($coordinatesService): void {
                $mappingVersion = $killZone->dungeonRoute->mappingVersion;

                $convertedLatLng = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $killZone->getLatLng());

                $newFloor = $convertedLatLng->getFloor();

                if ($newFloor === null || $newFloor->facade) {
                    return;
                }

                $killZone->update([
                    'floor_id' => $newFloor->id,
                    'lat'      => $convertedLatLng->getLat(),
                    'lng'      => $convertedLatLng->getLng(),
                ]);
            });
    }

    public function down(): void
    {
        //
    }
};
