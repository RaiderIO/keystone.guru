<?php

namespace App\Service\Floor\Dtos;

use App\Models\Floor\Floor;

/**
 * The result of resolving a raw, request-supplied floor index to an actual Floor.
 */
class ResolvedFloor
{
    /**
     * @param Floor $floor       The floor to use.
     * @param bool  $isCanonical False when $floor does not match the effective requested index (the
     *                           raw index falls back to 1 first when it is non-numeric) - callers
     *                           that redirect to a canonical URL should redirect to $floor->index in
     *                           this case. Note this does NOT flag a non-numeric raw index whose
     *                           fallback-to-1 happens to match an existing floor 1 - the same is
     *                           true of the controller logic this replaces.
     */
    public function __construct(
        public readonly Floor $floor,
        public readonly bool  $isCanonical,
    ) {
    }
}
