<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Support\Arrayable;

class CombatLogRouteSettingsRequestModel extends RequestModel implements Arrayable
{
    public function __construct(
        public ?bool $temporary = null,
        public ?bool $debugIcons = null
    ) {
    }
}
