<?php

namespace App\Http\Requests\Heatmap;

use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\GameServerRegion;
use Illuminate\Validation\Rule;

/**
 * All options that a user can pass to the explore URL to generate a heatmap
 *
 * @deprecated
 */
class ExploreUrlFormRequest extends HeatmapUrlFormRequest
{

}
