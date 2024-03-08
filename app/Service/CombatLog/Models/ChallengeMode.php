<?php

namespace App\Service\CombatLog\Models;

use App\Models\Dungeon;
use Carbon\Carbon;

class ChallengeMode
{
    public function __construct(private readonly Carbon $carbon, private readonly Dungeon $dungeon, private readonly int $keyLevel)
    {
    }

    public function getCarbon(): Carbon
    {
        return $this->carbon;
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    public function getKeyLevel(): int
    {
        return $this->keyLevel;
    }
}
