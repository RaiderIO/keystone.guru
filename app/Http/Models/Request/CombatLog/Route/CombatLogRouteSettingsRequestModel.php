<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @OA\Schema(schema="CombatLogRouteSettings")
 * @OA\Property(property="temporary", type="boolean", nullable=true)
 * @OA\Property(property="debugIcons", type="boolean", nullable=true)
 */
class CombatLogRouteSettingsRequestModel extends RequestModel implements Arrayable
{
    public function __construct(
        public ?bool $temporary = null,
        public ?bool $debugIcons = null
    ) {
    }
}
