<?php

namespace App\Service\CombatLog\Models\CreateRoute;

class CreateRouteChallengeMode
{
    /**
     * @param int|null $challengeModeId
     */
    public function __construct(public string $start, public string $end, public bool $success, public int $durationMs, public int $mapId, public ?int $challengeModeId, public int $level, public array $affixes)
    {
    }

    /**
     * @return CreateRouteChallengeMode
     */
    public static function createFromArray(array $body): CreateRouteChallengeMode
    {
        return new CreateRouteChallengeMode(
            $body['start'],
            $body['end'],
            $body['success'] ?? true,
            $body['durationMs'],
            $body['mapId'],
            $body['challengeModeId'] ?? null,
            $body['level'],
            $body['affixes'],
        );
    }
}
