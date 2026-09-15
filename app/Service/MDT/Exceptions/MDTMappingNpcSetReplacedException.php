<?php

namespace App\Service\MDT\Exceptions;

use Exception;

/**
 * Thrown by {@see \App\Service\MDT\MDTMappingImportService::importMappingVersionFromMDT()} when too few of
 * the NPCs MDT offers for a dungeon appear in the mapping version it is about to replace.
 *
 * Across all 307 mapping-version transitions in the database at the time of writing, the lowest overlap of a
 * real transition was 59%, so falling under
 * {@see \App\Service\MDT\MDTMappingImportService::NPC_SET_OVERLAP_MINIMUM} means something is wrong with
 * the incoming data - most likely MDT handing us another dungeon's, which is exactly what MDT 6.2.1 did,
 * shipping a `TheBlindingVale.lua` that was a duplicate of Den of Nalorakk's and replacing The Blinding
 * Vale's entire mapping without a single warning (#3995).
 *
 * Pass `$forceImport` to importMappingVersionFromMDT() (`--force` on `mdt:importmapping`) when a wholesale
 * remap really is intended.
 */
class MDTMappingNpcSetReplacedException extends Exception
{
    public function __construct(
        string  $dungeonKey,
        int     $previousNpcCount,
        int     $incomingNpcCount,
        int     $keptPercentage,
        ?string $looksLikeDungeonKey,
    ) {
        $message = sprintf(
            'MDT offers %d NPC(s) for dungeon %s, of which only %d%% appear in the %d NPC(s) of its ' .
            'current mapping version - refusing to replace the mapping.',
            $incomingNpcCount,
            $dungeonKey,
            $keptPercentage,
            $previousNpcCount,
        );

        if ($looksLikeDungeonKey !== null) {
            $message .= sprintf(' The incoming NPCs belong to dungeon %s - MDT is very likely shipping the ' .
                'wrong dungeon\'s data in its .lua file.', $looksLikeDungeonKey);
        }

        $message .= ' Pass --force if this really is intended.';

        parent::__construct($message);
    }
}
