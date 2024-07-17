<?php

namespace App\Service\Wowhead\Dtos;

class SpellDataResult
{
    public function __construct(
        private readonly int    $spellId,
        private readonly string $cooldownGroup,
        private readonly string $iconName,
        private readonly string $name,
        private readonly int    $schoolsMask,
        private readonly bool   $aura,
    ) {

    }

    public function getSpellId(): int
    {
        return $this->spellId;
    }

    public function getCooldownGroup(): string
    {
        return $this->cooldownGroup;
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

    public function isAura(): bool
    {
        return $this->aura;
    }
}
