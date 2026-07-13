<?php

namespace App\Service\CombatLog\Splitters;

use Illuminate\Support\Collection;

interface CombatLogSplitterInterface
{
    /**
     * @param  string                  $filePath
     * @return Collection<int, string>
     */
    public function splitCombatLog(string $filePath): Collection;
}
