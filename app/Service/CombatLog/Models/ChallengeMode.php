<?php

namespace App\Service\CombatLog\Models;

use App\Models\Dungeon;
use Carbon\Carbon;

class ChallengeMode
{
    private Carbon  $carbon;
    
    private Dungeon $dungeon;

    private int $keyLevel;
    /**
     * @param \Carbon\Carbon      $carbon
     * @param \App\Models\Dungeon $dungeon
     * @param int                 $keyLevel
     */
    public function __construct(Carbon $carbon, Dungeon $dungeon, int $keyLevel)
    {
        $this->carbon   = $carbon;
        $this->dungeon  = $dungeon;
        $this->keyLevel = $keyLevel;
    }
    /**
     * @return \Carbon\Carbon
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