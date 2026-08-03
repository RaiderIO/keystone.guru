<?php

namespace App\Dto\Request\CombatLog\Route;

use App\Dto\Request\RequestDto;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @OA\Schema(schema="CombatLogRouteRoster")
 * @OA\Property(property="numMembers", type="integer")
 * @OA\Property(property="averageItemLevel", type="number", format="float")
 * @OA\Property(property="characterIds", type="array", @OA\Items(type="integer"))
 * @OA\Property(property="specIds", type="array", @OA\Items(type="integer"))
 * @OA\Property(property="classIds", type="array", @OA\Items(type="integer"))
 * @implements Arrayable<string, mixed>
 */
class CombatLogRouteRosterRequestDto extends RequestDto implements Arrayable
{
    /**
     * @param array<int>|null $characterIds
     * @param array<int>|null $specIds
     * @param array<int>|null $classIds
     */
    public function __construct(
        public ?int   $numMembers = null,
        public ?float $averageItemLevel = null,
        public ?array $characterIds = null,
        public ?array $specIds = null,
        public ?array $classIds = null,
    ) {
    }
}
