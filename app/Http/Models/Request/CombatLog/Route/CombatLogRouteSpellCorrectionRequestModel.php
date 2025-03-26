<?php

namespace App\Http\Models\Request\CombatLog\Route;

/**
 * @OA\Schema(schema="CombatLogRouteSpellCorrection")
 * @OA\Property(property="spellId",type="integer")
 * @OA\Property(property="playerUid",type="string")
 * @OA\Property(property="castAt",type="string",format="date-time")
 * @OA\Property(property="coord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 * @OA\Property(property="gridCoord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 */
class CombatLogRouteSpellCorrectionRequestModel extends CombatLogRouteSpellRequestModel
{
    public function __construct(
        ?int                                    $spellId = null,
        ?string                                 $playerUid = null,
        ?string                                 $castAt = null,
        ?CombatLogRouteCoordRequestModel        $coord = null,
        public ?CombatLogRouteCoordRequestModel $gridCoord = null
    ) {
        parent::__construct($spellId, $playerUid, $castAt, $coord);
    }
}
