<?php

namespace App\Service\CombatLog\Models\CreateRoute;

class CreateRouteMetadata
{
    public string $runId;

    /**
     * @param string $runId
     */
    public function __construct(string $runId)
    {
        $this->runId = $runId;
    }

    /**
     * @param array $body
     * @return CreateRouteMetadata
     */
    public static function createFromArray(array $body): CreateRouteMetadata
    {
        return new CreateRouteMetadata(
            $body['runId']
        );
    }
}
