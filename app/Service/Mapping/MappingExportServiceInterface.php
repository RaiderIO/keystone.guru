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

    /**
     * Serializes every spell tuning change to the array written to spell_tuning_changes.json, in a
     * stable order and without ids so the file only changes when the data does.
     *
     * @return array<int, array<string, mixed>>
     */
    public function serializeSpellTuningChanges(): array;
}
