<?php

namespace App\Service\Floor\Dtos;

use App\Models\Floor\Floor;

/**
 * The result of resolving a raw, request-supplied floor index to an actual Floor.
 */
class ResolvedFloor
{
    /**
     * @param Floor $floor         The floor to use.
     * @param bool  $isCanonical   False when $floor does not match the effective requested index (the
     *                             raw index falls back to 1 first when it is non-numeric) - callers
     *                             that redirect to a canonical URL should redirect to $floor->index in
     *                             this case. Note this does NOT flag a non-numeric raw index whose
     *                             fallback-to-1 happens to match an existing floor 1 - the same is
     *                             true of the controller logic this replaces.
     * @param bool  $floorWasFound False when no floor matched the effective requested index at all and
     *                             $floor is therefore the default/facade floor rather than an actual
     *                             match - distinct from $isCanonical, which is also false for a floor
     *                             that *was* found but under a different index (e.g. facade forcing).
     *                             Most callers only need $isCanonical; this exists for the one caller
     *                             (LiveSessionController::viewFloor()) that redirects only when nothing
     *                             matched at all, and otherwise renders whatever was found as-is.
     */
    public function __construct(
        public readonly Floor $floor,
        public readonly bool  $isCanonical,
        public readonly bool  $floorWasFound,
    ) {
    }
}
