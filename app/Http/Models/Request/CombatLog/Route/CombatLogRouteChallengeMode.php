<?php

namespace App\Http\Models\Request\CombatLog\Route;

use Illuminate\Contracts\Support\Arrayable;

class CombatLogRouteChallengeMode implements Arrayable
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

    public static function createFromArray(array $body): CombatLogRouteChallengeMode
    {
        return new CombatLogRouteChallengeMode(
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
