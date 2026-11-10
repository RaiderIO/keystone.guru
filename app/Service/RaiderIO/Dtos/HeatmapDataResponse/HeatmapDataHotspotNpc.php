<?php

namespace App\Service\RaiderIO\Dtos\HeatmapDataResponse;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class HeatmapDataHotspotNpc implements Arrayable
{
    private int $npcId;

    /** Null when the NPC is unknown to Keystone.guru */
    private ?string $name = null;

    /** Relative to the assets base url; null when the NPC is unknown to Keystone.guru */
    private ?string $portraitUrl = null;

    private int $deaths;

    /** Share of the hotspot's deaths, 0-100 */
    private float $percentage;

    private function __construct()
    {
    }

    public function getNpcId(): int
    {
        return $this->npcId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPortraitUrl(): ?string
    {
        return $this->portraitUrl;
    }

    public function getDeaths(): int
    {
        return $this->deaths;
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

    /**
     * @return array{npc_id: int, name: string|null, portrait_url: string|null, deaths: int, percentage: float}
     */
    public function toArray(): array
    {
        return [
            'npc_id'       => $this->npcId,
            'name'         => $this->name,
            'portrait_url' => $this->portraitUrl,
            'deaths'       => $this->deaths,
            'percentage'   => $this->percentage,
        ];
    }

    /**
     * @param array{npc_id: int, name: string|null, portrait_url: string|null, deaths: int, percentage: float} $data
     */
    public static function fromArray(array $data): self
    {
        $instance              = new self();
        $instance->npcId       = $data['npc_id'];
        $instance->name        = $data['name'];
        $instance->portraitUrl = $data['portrait_url'];
        $instance->deaths      = $data['deaths'];
        $instance->percentage  = $data['percentage'];

        return $instance;
    }
}
