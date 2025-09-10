<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @OA\Schema(schema="CombatLogRouteRoster")
 * @OA\Property(property="numMembers", type="integer")
 * @OA\Property(property="averageItemLevel", type="number", format="float")
 * @OA\Property(property="characterIds", type="array", @OA\Items(type="integer"))
 * @OA\Property(property="specIds", type="array", @OA\Items(type="integer"))
 * @OA\Property(property="classIds", type="array", @OA\Items(type="integer"))
 */
class CombatLogRouteRosterRequestModel extends RequestModel implements Arrayable
{
    public function __construct(
        public ?int   $numMembers = null,
        public ?float $averageItemLevel = null,
        public ?array $characterIds = null,
        public ?array $specIds = null,
        public ?array $classIds = null,
    ) {
    }
}
