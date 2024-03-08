<?php

namespace App\Service\CombatLog\Models\CreateRoute;

class CreateRouteMetadata
{
    public function __construct(public string $runId)
    {
    }

    public static function createFromArray(array $body): CreateRouteMetadata
    {
        return new CreateRouteMetadata(
            $body['runId']
        );
    }
}
