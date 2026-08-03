<?php

namespace App\Dto\Request\CombatLog\Route;

use App\Dto\Request\RequestDTO;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @OA\Schema(schema="CombatLogRouteChallengeMode")
 * @OA\Property(property="start",type="string",format="date-time")
 * @OA\Property(property="end",type="string",format="date-time")
 * @OA\Property(property="success",type="boolean",nullable=true)
 * @OA\Property(property="durationMs",type="integer")
 * @OA\Property(property="parTimeMs",type="integer")
 * @OA\Property(property="timerFraction",type="number",format="float")
 * @OA\Property(property="challengeModeId",type="integer")
 * @OA\Property(property="level",type="integer")
 * @OA\Property(property="numDeaths",type="integer")
 * @OA\Property(property="affixes",type="array", @OA\Items(type="integer"))
 * @implements Arrayable<string, mixed>
 */
class CombatLogRouteChallengeModeRequestDTO extends RequestDTO implements Arrayable
{
    /**
     * @param array<int>|null $affixes
     */
    public function __construct(
        public ?string $start = null,
        public ?string $end = null,
        public ?bool   $success = null,
        public ?int    $durationMs = null,
        public ?int    $parTimeMs = null,
        public ?float  $timerFraction = null,
        public ?int    $challengeModeId = null,
        public ?int    $level = null,
        public ?int    $numDeaths = null,
        public ?array  $affixes = null,
    ) {
    }
}
