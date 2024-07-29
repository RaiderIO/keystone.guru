<?php

namespace App\Service\CombatLog\Splitters;

use Illuminate\Support\Collection;

interface CombatLogSplitterInterface
{
    /**
     * @param string $filePath
     * @return Collection<string>
     */
    public function splitCombatLog(string $filePath): Collection;
}
