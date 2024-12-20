<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @OA\Schema(schema="CombatLogRouteMetadata")
 * @OA\Property(property="runId",type="string")
 * @OA\Property(property="keystoneRunId",type="integer",nullable=true)
 * @OA\Property(property="loggedRunId",type="integer")
 * @OA\Property(property="period",type="integer",description="The week of the season this run was recorded in, where 1 is the first week of the season")
 * @OA\Property(property="season",type="string")
 * @OA\Property(property="regionId",type="integer",description="The ID of the region that this run was recorded on")
 * @OA\Property(property="realmType",type="string",description="The type of realm, such as 'live', 'beta' or 'ptr' etc.")
 * @OA\Property(property="wowInstanceId",type="integer",nullable=true)
 */
class CombatLogRouteMetadataRequestModel extends RequestModel implements Arrayable
{
    public function __construct(
        public ?string $runId = null,
        public ?int    $keystoneRunId = null,
        public ?int    $loggedRunId = null,
        public ?int    $period = null,
        public ?string $season = null,
        public ?int    $regionId = null,
        public ?string $realmType = null,
        public ?int    $wowInstanceId = null,
    ) {
    }
}
