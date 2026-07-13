<?php

namespace App\Service\RaiderIO\Dtos;

class SearchAdvancedRun
{
    /**
     * @param int[] $memberSpecIds Blizzard spec IDs of all party members.
     * @param int[] $affixes       Affix IDs active during the run.
     */
    public function __construct(
        public readonly int   $id,
        public readonly int   $challengeModeId,
        public readonly int   $dungeonZoneId,
        public readonly array $memberSpecIds,
        public readonly int   $mythicLevel,
        public readonly array $affixes,
    ) {
    }

    /**
     * @param array<string, mixed> $data The `data` object from a single search-advanced match.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id:              (int)$data['id'],
            challengeModeId: (int)$data['challengeModeId'],
            dungeonZoneId:   (int)$data['dungeonZoneId'],
            memberSpecIds:   array_map(intval(...), $data['memberSpecIds'] ?? []),
            mythicLevel:     (int)$data['mythicLevel'],
            affixes:         array_map(intval(...), $data['affixes'] ?? []),
        );
    }
}
