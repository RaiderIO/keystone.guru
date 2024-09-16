<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Illuminate\Contracts\Support\Arrayable;

class CreateRouteMetadata implements Arrayable
{
    public function __construct(public string $runId)
    {
    }

    public function toArray(): array
    {
        return [
            'runId' => $this->runId
        ];
    }

    public static function createFromArray(array $body): CreateRouteMetadata
    {
        return new CreateRouteMetadata(
            $body['runId']
        );
    }
}
