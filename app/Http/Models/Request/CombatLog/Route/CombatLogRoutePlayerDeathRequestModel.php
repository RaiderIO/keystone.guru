<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use App\Models\Enemy;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * @OA\Schema(schema="CombatLogRoutePlayerDeath")
 * @OA\Property(property="characterId",type="integer")
 * @OA\Property(property="classId",type="integer")
 * @OA\Property(property="specId",type="integer")
 * @OA\Property(property="itemLevel",type="number",format="float")
 * @OA\Property(property="diedAt",type="string",format="date-time")
 * @OA\Property(property="coord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 * @OA\Property(property="gridCoord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 */
class CombatLogRoutePlayerDeathRequestModel extends RequestModel implements Arrayable
{
    private Carbon $diedAtCarbon;

    private ?Enemy $resolvedEnemy = null;

    public function __construct(
        public ?int                             $characterId = null,
        public ?int                             $classId = null,
        public ?int                             $specId = null,
        public ?float                           $itemLevel = null,
        public ?string                          $diedAt = null,
        public ?CombatLogRouteCoordRequestModel $coord = null,
        public ?CombatLogRouteCoordRequestModel $gridCoord = null
    ) {
    }

    public function getDiedAt(): Carbon
    {
        return $this->diedAtCarbon ??
            $this->diedAtCarbon = Carbon::createFromFormat(CombatLogRouteRequestModel::DATE_TIME_FORMAT, $this->diedAt);
    }
}
