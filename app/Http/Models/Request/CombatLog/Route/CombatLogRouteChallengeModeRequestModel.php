<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Support\Arrayable;

class CombatLogRouteChallengeModeRequestModel extends RequestModel implements Arrayable
{
    public function __construct(
        public ?string $start = null,
        public ?string $end = null,
        public ?bool   $success = null,
        public ?int    $durationMs = null,
        public ?int    $challengeModeId = null,
        public ?int    $level = null,
        public ?array  $affixes = null)
    {
    }
}
