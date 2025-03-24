<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Models\Enemy;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @OA\Schema(schema="CombatLogRoutePlayerDeathCorrection")
 * @OA\Property(property="characterId",type="integer")
 * @OA\Property(property="classId",type="integer")
 * @OA\Property(property="specId",type="integer")
 * @OA\Property(property="itemLevel",type="number",format="float")
 * @OA\Property(property="diedAt",type="string",format="date-time")
 * @OA\Property(property="coord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 * @OA\Property(property="gridCoord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 */
class CombatLogRoutePlayerDeathCorrectionRequestModel extends CombatLogRoutePlayerDeathRequestModel
{
    private ?Enemy $resolvedEnemy = null;

    public function __construct(
        ?int                                    $characterId = null,
        ?int                                    $classId = null,
        ?int                                    $specId = null,
        ?float                                  $itemLevel = null,
        ?string                                 $diedAt = null,
        ?CombatLogRouteCoordRequestModel        $coord = null,
        public ?CombatLogRouteCoordRequestModel $gridCoord = null
    ) {
        parent::__construct($characterId, $classId, $specId, $itemLevel, $diedAt, $coord);
    }
}
