<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @OA\Schema(schema="CombatLogRouteCoord")
 * @OA\Property(type="number",format="float",property="x")
 * @OA\Property(type="number",format="float",property="y")
 * @OA\Property(type="integer",property="uiMapId")
 */
class CombatLogRouteCoordRequestModel extends RequestModel implements Arrayable
{
    public function __construct(
        public ?float $x = null,
        public ?float $y = null,
        public ?int   $uiMapId = null
    ) {
    }
}
