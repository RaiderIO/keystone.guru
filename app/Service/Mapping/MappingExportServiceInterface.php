<?php

namespace App\Service\Mapping;

interface MappingExportServiceInterface
{
    /**
     * Serializes all spells to the behavior-stripped array that is written to spells.json.
     *
     * @return array<int, array<string, mixed>>
     */
    public function serializeSpells(): array;

    /**
     * Serializes all NPCs to the behavior-stripped array that is written to npcs.json.
     *
     * @return array<int, array<string, mixed>>
     */
    public function serializeNpcs(): array;
}
