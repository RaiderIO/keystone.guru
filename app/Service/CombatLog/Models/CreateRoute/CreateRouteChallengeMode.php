<?php

namespace App\Service\CombatLog\Models\CreateRoute;

class CreateRouteChallengeMode
{
    public string $start;

    public string $end;

    public bool $success;

    public int $durationMs;

    public int $mapId;

    public int $level;

    public array $affixes;

    /**
     * @param string $start
     * @param string $end
     * @param bool   $success
     * @param int    $durationMs
     * @param int    $mapId
     * @param int    $level
     * @param array  $affixes
     */
    public function __construct(
        string $start,
        string $end,
        bool   $success,
        int    $durationMs,
        int    $mapId,
        int    $level,
        array  $affixes)
    {
        $this->start      = $start;
        $this->end        = $end;
        $this->success    = $success;
        $this->durationMs = $durationMs;
        $this->mapId      = $mapId;
        $this->level      = $level;
        $this->affixes    = $affixes;
    }

    /**
     * @param array $body
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
            $body['level'],
            $body['affixes'],
        );
    }
}
