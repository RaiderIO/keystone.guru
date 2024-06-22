<?php

namespace App\Http\Resources\RaiderIO;

use App\Service\CombatLogEvent\Models\CombatLogEventGridAggregationResult;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CombatLogEventGridAggregationResultResource
 *
 * @author Wouter
 *
 * @since 20/01/2024
 *
 * @mixin CombatLogEventGridAggregationResult
 */
class CombatLogEventGridAggregationResultResource extends JsonResource
{
    // toArray is already implemented in CombatLogEventGridAggregationResult
}
