<?php

namespace App\Dto\Request\CombatLog\Route;

/**
 * @OA\Schema(schema="CombatLogRouteSpellCorrection")
 * @OA\Property(property="spellId",type="integer")
 * @OA\Property(property="playerUid",type="string")
 * @OA\Property(property="castAt",type="string",format="date-time")
 * @OA\Property(property="coord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 * @OA\Property(property="gridCoord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 */
class CombatLogRouteSpellCorrectionRequestDTO extends CombatLogRouteSpellRequestDTO
{
    public function __construct(
        ?int                                  $spellId = null,
        ?string                               $playerUid = null,
        ?string                               $castAt = null,
        ?CombatLogRouteCoordRequestDTO        $coord = null,
        public ?CombatLogRouteCoordRequestDTO $gridCoord = null,
    ) {
        parent::__construct($spellId, $playerUid, $castAt, $coord);
    }
}
