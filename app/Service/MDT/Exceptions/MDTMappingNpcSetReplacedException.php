<?php

namespace App\Service\MDT\Exceptions;

use Exception;

/**
 * Thrown by {@see \App\Service\MDT\MDTMappingImportService::importMappingVersionFromMDT()} when the NPCs MDT
 * offers for a dungeon have *nothing* in common with the ones its previous mapping version holds.
 *
 * A dungeon cannot legitimately be remapped onto a completely disjoint set of NPCs: across all 307
 * mapping-version transitions in the database at the time of writing, the lowest overlap of a real transition
 * was 59%, and not one was 0%. A 0% overlap means MDT handed us another dungeon's data - which is exactly
 * what MDT 6.2.1 did, shipping a `TheBlindingVale.lua` that was a duplicate of Den of Nalorakk's, replacing
 * The Blinding Vale's entire mapping without a single warning (#3995).
 *
 * Pass `$allowNpcSetReplacement` to importMappingVersionFromMDT() (`--allow-npc-set-replacement` on
 * `mdt:importmapping`) when a wholesale remap really is intended.
 */
class MDTMappingNpcSetReplacedException extends Exception
{
    public function __construct(
        string  $dungeonKey,
        int     $previousNpcCount,
        int     $incomingNpcCount,
        ?string $looksLikeDungeonKey,
    ) {
        $message = sprintf(
            'MDT offers %d NPC(s) for dungeon %s that have NOTHING in common with the %d NPC(s) of its ' .
            'current mapping version - refusing to replace the mapping.',
            $incomingNpcCount,
            $dungeonKey,
            $previousNpcCount,
        );

        if ($looksLikeDungeonKey !== null) {
            $message .= sprintf(' The incoming NPCs belong to dungeon %s - MDT is very likely shipping the ' .
                'wrong dungeon\'s data in its .lua file.', $looksLikeDungeonKey);
        }

        $message .= ' Pass --allow-npc-set-replacement if this really is intended.';

        parent::__construct($message);
    }
}
