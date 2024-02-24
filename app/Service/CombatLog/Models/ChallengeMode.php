<?php

namespace App\Service\CombatLog\Models;

use App\Models\Dungeon;
use Carbon\Carbon;

class ChallengeMode
{
    public function __construct(private Carbon $carbon, private Dungeon $dungeon, private int $keyLevel)
    {
    }

    /**
     * @return Carbon
     */
    public function getCarbon(): Carbon
    {
        return $this->carbon;
    }

    /**
     * @return Dungeon
     */
    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }

    /**
     * @return int
     */
    public function getKeyLevel(): int
    {
        return $this->keyLevel;
    }

}
