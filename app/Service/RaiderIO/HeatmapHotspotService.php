<?php

namespace App\Service\RaiderIO;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\RaiderIOHeatmapHotspot;
use Illuminate\Support\Collection;

class HeatmapHotspotService implements HeatmapHotspotServiceInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly NpcRepositoryInterface      $npcRepository,
    ) {
    }

    public function convertHotspotsByFloor(
        Dungeon         $dungeon,
        ?MappingVersion $mappingVersion,
        array           $hotspotsByFloor,
        bool            $useFacade,
        bool            $useFacadeNavigation,
    ): array {
        /** @var Collection<int, Floor> $floors */
        $floors = $dungeon->floors->keyBy('id');

        $hotspots = $this->parseHotspots($floors, $hotspotsByFloor);
        $npcsById = $this->resolveNpcs(
            $hotspots->flatMap(
                fn(RaiderIOHeatmapHotspot $hotspot) => array_column(array_slice($hotspot->npcs, 0, $this->getMaxNpcs()), 'npcId'),
            )->unique()->values(),
        );

        $result = [];

        /** @var Floor|null $facadeFloor */
        $facadeFloor = $useFacade ? $floors->first(static fn(Floor $floor): bool => $floor->facade) : null;
        if ($facadeFloor instanceof Floor && $mappingVersion !== null) {
            $result[] = [
                'floor_id' => $facadeFloor->id,
                'hotspots' => $this->convertHotspotsForFacade($mappingVersion, $facadeFloor, $hotspots, $npcsById),
            ];

            // Without facade navigation the zone floors cannot be opened, so they need no hotspots of their own
            if (!$useFacadeNavigation) {
                return $result;
            }
        }

        foreach ($hotspots->groupBy(static fn(RaiderIOHeatmapHotspot $hotspot): int => $hotspot->floor->id) as $floorId => $floorHotspots) {
            $result[] = [
                'floor_id' => $floorId,
                'hotspots' => $this->removeOverlappingHotspots($floorHotspots)->map(
                    fn(RaiderIOHeatmapHotspot $hotspot) => $this->buildHotspot(
                        $hotspot,
                        $npcsById,
                        fn(IngameXY $ingameXY): LatLng => $this->coordinatesService->calculateMapLocationForIngameLocation($ingameXY),
                    ),
                )->values()->all(),
            ];
        }

        return $result;
    }

    /**
     * On the facade every zone shrinks to a part of the continent: a hotspot too small to read there is left out (it
     * still shows on its own zone floor), and so is one with no floor union to place it with.
     *
     * @param  Collection<int, RaiderIOHeatmapHotspot>                    $hotspots
     * @param  Collection<int, array{name: string, portrait_url: string}> $npcsById
     * @return list<array<string, mixed>>
     */
    private function convertHotspotsForFacade(
        MappingVersion $mappingVersion,
        Floor          $facadeFloor,
        Collection     $hotspots,
        Collection     $npcsById,
    ): array {
        $facadeArea      = abs(($facadeFloor->ingame_max_x - $facadeFloor->ingame_min_x) * ($facadeFloor->ingame_max_y - $facadeFloor->ingame_min_y));
        $minAreaFraction = (float)config('keystoneguru.heatmap.hotspots.facade_min_area_fraction');

        $visibleHotspots = $hotspots->filter(
            static fn(RaiderIOHeatmapHotspot $hotspot): bool => $facadeArea <= 0 || $hotspot->area / $facadeArea >= $minAreaFraction,
        );

        $result = [];
        foreach ($this->removeOverlappingHotspots($visibleHotspots) as $hotspot) {
            // Every vertex goes through the floor union of the centre, so a polygon reaching past the edge of its zone
            // still lands on the facade in one piece
            $floorUnion = $mappingVersion->getFloorUnionForLatLng(
                $this->coordinatesService,
                $this->coordinatesService->calculateMapLocationForIngameLocation($hotspot->centroid),
            );

            if (!$floorUnion instanceof FloorUnion) {
                continue;
            }

            $result[] = $this->buildHotspot(
                $hotspot,
                $npcsById,
                fn(IngameXY $ingameXY): LatLng => $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                    $mappingVersion,
                    $this->coordinatesService->calculateMapLocationForIngameLocation($ingameXY),
                    $floorUnion,
                ),
            );
        }

        return $result;
    }

    /**
     * The most deadly hotspot wins: one whose centre lies inside a more deadly hotspot that is kept is left out.
     *
     * @param  Collection<int, RaiderIOHeatmapHotspot> $hotspots
     * @return Collection<int, RaiderIOHeatmapHotspot>
     */
    private function removeOverlappingHotspots(Collection $hotspots): Collection
    {
        /** @var Collection<int, RaiderIOHeatmapHotspot> $result */
        $result = collect();

        foreach ($hotspots->sortByDesc(static fn(RaiderIOHeatmapHotspot $hotspot): int => $hotspot->deaths) as $hotspot) {
            $overlapsKeptHotspot = $result->contains(
                static fn(RaiderIOHeatmapHotspot $keptHotspot): bool => $keptHotspot->containsPoint($hotspot->centroid),
            );

            if (!$overlapsKeptHotspot) {
                $result->push($hotspot);
            }
        }

        return $result;
    }

    /**
     * @param  Collection<int, array{name: string, portrait_url: string}> $npcsById
     * @param  callable(IngameXY): LatLng                                 $toLatLng
     * @return array<string, mixed>
     */
    private function buildHotspot(RaiderIOHeatmapHotspot $hotspot, Collection $npcsById, callable $toLatLng): array
    {
        return [
            'lat_lngs' => array_map(
                fn(IngameXY $vertex): array => $this->toRoundedLatLngArray($toLatLng($vertex)),
                $hotspot->vertices,
            ),
            'center' => $this->toRoundedLatLngArray($toLatLng($hotspot->centroid)),
            'deaths' => $hotspot->deaths,
            'npcs'   => array_map(static fn(array $npc): array => [
                'npc_id'       => $npc['npcId'],
                'name'         => $npcsById->get($npc['npcId'])['name'] ?? null,
                'portrait_url' => $npcsById->get($npc['npcId'])['portrait_url'] ?? null,
                'deaths'       => $npc['deaths'],
                'percentage'   => round($npc['deaths'] / $hotspot->deaths * 100, 1),
            ], array_slice($hotspot->npcs, 0, $this->getMaxNpcs())),
        ];
    }

    /**
     * @return array{lat: float, lng: float}
     */
    private function toRoundedLatLngArray(LatLng $latLng): array
    {
        return [
            'lat' => round($latLng->getLat(), 2),
            'lng' => round($latLng->getLng(), 2),
        ];
    }

    /**
     * @param  Collection<int, Floor>                  $floors
     * @param  array<int|string, mixed>                $hotspotsByFloor
     * @return Collection<int, RaiderIOHeatmapHotspot>
     */
    private function parseHotspots(Collection $floors, array $hotspotsByFloor): Collection
    {
        /** @var Collection<int, RaiderIOHeatmapHotspot> $result */
        $result = collect();

        foreach ($hotspotsByFloor as $floorId => $hotspots) {
            /** @var Floor|null $floor */
            $floor = $floors->get((int)$floorId);
            if (!$floor instanceof Floor || $floor->facade || !is_array($hotspots)) {
                continue;
            }

            foreach ($hotspots as $hotspot) {
                $parsedHotspot = is_array($hotspot) ? RaiderIOHeatmapHotspot::fromArray($floor, $hotspot) : null;
                if ($parsedHotspot !== null) {
                    $result->push($parsedHotspot);
                }
            }
        }

        return $result;
    }

    /**
     * @param  Collection<int, int>                                       $npcIds
     * @return Collection<int, array{name: string, portrait_url: string}>
     */
    private function resolveNpcs(Collection $npcIds): Collection
    {
        if ($npcIds->isEmpty()) {
            return collect();
        }

        $result = $this->npcRepository->findAllById($npcIds)->map(static fn(Npc $npc): array => [
            'name'         => (string)__($npc->name),
            'portrait_url' => $npc->enemy_portrait_url,
        ]);

        return $result->union($this->resolveOpenWorldNpcs($npcIds->diff($result->keys())->values()));
    }

    /**
     * The NPCs that are not in `npcs`. Nothing resolves them against the open-world NPCs, so each of them gets
     * the unknown portrait.
     *
     * @param  Collection<int, int>                                       $npcIds
     * @return Collection<int, array{name: string, portrait_url: string}>
     */
    private function resolveOpenWorldNpcs(Collection $npcIds): Collection
    {
        return collect();
    }

    private function getMaxNpcs(): int
    {
        return (int)config('keystoneguru.heatmap.hotspots.max_npcs');
    }
}
