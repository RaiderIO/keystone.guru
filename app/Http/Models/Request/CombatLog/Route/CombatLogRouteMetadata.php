<?php

namespace App\Http\Models\Request\CombatLog\Route;

use Illuminate\Contracts\Support\Arrayable;

class CombatLogRouteMetadata implements Arrayable
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

    public static function createFromArray(array $body): CombatLogRouteMetadata
    {
        return new CombatLogRouteMetadata(
            $body['runId']
        );
    }
}
