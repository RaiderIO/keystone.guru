<?php

namespace App\Dto\Request\CombatLog\Route;

use App\Dto\Request\RequestDTO;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @OA\Schema(schema="CombatLogRouteCoord")
 * @OA\Property(type="number",format="float",property="x")
 * @OA\Property(type="number",format="float",property="y")
 * @OA\Property(type="integer",property="uiMapId")
 * @implements Arrayable<string, mixed>
 */
class CombatLogRouteCoordRequestDTO extends RequestDTO implements Arrayable
{
    public function __construct(
        public ?float $x = null,
        public ?float $y = null,
        public ?int   $uiMapId = null,
    ) {
    }
}
