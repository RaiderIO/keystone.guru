<?php

namespace App\Service\CombatLogEvent\Models;

use App\Models\Dungeon;
use RectorPrefix202402\Illuminate\Contracts\Support\Arrayable;

class CombatLogEventFilter implements Arrayable
{
    public function __construct(
        private readonly ?Dungeon $dungeon = null
    ) {
    }

    public function getDungeon(): ?Dungeon
    {
        return $this->dungeon;
    }

    public function toArray(): array
    {
        return [
            'challenge_mode_id' => $this->dungeon->challenge_mode_id,
        ];
    }

    public static function fromArray(array $requestArray): CombatLogEventFilter
    {
        return new CombatLogEventFilter(
            dungeon: Dungeon::firstWhere('id', $requestArray['dungeon_id'])
        );
    }
}
