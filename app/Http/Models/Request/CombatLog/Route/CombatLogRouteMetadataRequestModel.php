<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Support\Arrayable;

class CombatLogRouteMetadataRequestModel extends RequestModel implements Arrayable
{
    public function __construct(public ?string $runId = null)
    {
    }
}
