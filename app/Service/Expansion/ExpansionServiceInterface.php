<?php


namespace App\Service\Expansion;

use App\Models\Expansion;
use Carbon\Carbon;

interface ExpansionServiceInterface
{
    /**
     * @param Carbon $carbon
     * @return Expansion|null
     */
    public function getExpansionAt(Carbon $carbon): ?Expansion;

    /**
     * @return Expansion
     */
    public function getCurrentExpansion(): Expansion;
}