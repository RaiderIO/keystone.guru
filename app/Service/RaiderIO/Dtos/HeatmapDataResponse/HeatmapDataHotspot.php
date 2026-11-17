<?php

namespace App\Service\RaiderIO\Dtos\HeatmapDataResponse;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * An area where players die a lot, with the NPCs that kill them there.
 *
 * @implements Arrayable<string, mixed>
 */
class HeatmapDataHotspot implements Arrayable
{
    /** @var list<array{lat: float, lng: float}> */
    private array $latLngs;

    /** @var array{lat: float, lng: float} */
    private array $center;

    private int $deaths;

    /** @var Collection<int, HeatmapDataHotspotNpc> Sorted by deaths, most deadly first */
    private Collection $npcs;

    private function __construct()
    {
    }

    /**
     * @return list<array{lat: float, lng: float}>
     */
    public function getLatLngs(): array
    {
        return $this->latLngs;
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function getCenter(): array
    {
        return $this->center;
    }

    public function getDeaths(): int
    {
        return $this->deaths;
    }

    /**
     * @return Collection<int, HeatmapDataHotspotNpc>
     */
    public function getNpcs(): Collection
    {
        return $this->npcs;
    }

    public function toArray(): array
    {
        return [
            'lat_lngs' => $this->latLngs,
            'center'   => $this->center,
            'deaths'   => $this->deaths,
            'npcs'     => $this->npcs->map(fn(HeatmapDataHotspotNpc $npc) => $npc->toArray())->values()->toArray(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $instance          = new self();
        $instance->latLngs = $data['lat_lngs'];
        $instance->center  = $data['center'];
        $instance->deaths  = $data['deaths'];
        /** @var list<array{npc_id: int, name: string|null, portrait_url: string|null, deaths: int, percentage: float}> $npcs */
        $npcs           = $data['npcs'];
        $instance->npcs = collect($npcs)->map(fn(array $npc) => HeatmapDataHotspotNpc::fromArray($npc))->values();

        return $instance;
    }
}
