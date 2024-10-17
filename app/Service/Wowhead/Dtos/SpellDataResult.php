<?php

namespace App\Service\Wowhead\Dtos;

use Illuminate\Contracts\Support\Arrayable;

class SpellDataResult implements Arrayable
{
    public function __construct(
        private readonly int     $spellId,
        private readonly ?string $mechanic,
        private readonly string  $cooldownGroup,
        private readonly string  $dispelType,
        private readonly string  $iconName,
        private readonly string  $name,
        private readonly int     $schoolsMask,
        private readonly ?int    $castTime,
        private readonly ?int    $duration,
    ) {

    }

    public function getSpellId(): int
    {
        return $this->spellId;
    }

    public function getMechanic(): ?string
    {
        return $this->mechanic;
    }

    public function getCooldownGroup(): string
    {
        return $this->cooldownGroup;
    }

    public function getDispelType(): string
    {
        return $this->dispelType;
    }

    public function getIconName(): string
    {
        return $this->iconName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchoolsMask(): int
    {
        return $this->schoolsMask;
    }

    public function getCastTime(): ?int
    {
        return $this->castTime;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function toArray(): array
    {
        return [
            'spell_id'       => $this->spellId,
            'mechanic'       => $this->mechanic,
            'cooldown_group' => $this->cooldownGroup,
            'dispel_type'    => $this->dispelType,
            'icon_name'      => $this->iconName,
            'name'           => $this->name,
            'schools_mask'   => $this->schoolsMask,
            'cast_time'      => $this->castTime,
            'duration'       => $this->duration,
        ];
    }
}
