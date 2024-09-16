<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Illuminate\Contracts\Support\Arrayable;

class CreateRouteChallengeMode implements Arrayable
{
    public function __construct(
        public string $start,
        public string $end,
        public bool   $success,
        public int    $durationMs,
        public int    $challengeModeId,
        public int    $level,
        public array  $affixes)
    {
    }

    public function toArray(): array
    {
        return [
            'start'           => $this->start,
            'end'             => $this->end,
            'success'         => $this->success,
            'durationMs'      => $this->durationMs,
            'challengeModeId' => $this->challengeModeId,
            'level'           => $this->level,
            'affixes'         => $this->affixes,
        ];
    }

    public static function createFromArray(array $body): CreateRouteChallengeMode
    {
        return new CreateRouteChallengeMode(
            $body['start'],
            $body['end'],
            $body['success'] ?? true,
            $body['durationMs'],
            $body['challengeModeId'],
            $body['level'],
            $body['affixes'],
        );
    }
}
