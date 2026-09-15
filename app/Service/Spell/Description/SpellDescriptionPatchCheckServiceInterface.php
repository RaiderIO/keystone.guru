<?php

namespace App\Service\Spell\Description;

interface SpellDescriptionPatchCheckServiceInterface
{
    /**
     * Compares the latest wago.tools build for $product against the build we last actually imported for
     * $gameVersionId, and files a GitHub issue if they differ and no open issue for that build exists
     * yet. Does nothing (besides logging) when wago.tools cannot be reached or its response cannot be
     * parsed - a missed reminder is recoverable, a stream of spurious issues is not.
     */
    public function checkForPatch(string $product, string $gameVersionKey, int $gameVersionId): void;
}
