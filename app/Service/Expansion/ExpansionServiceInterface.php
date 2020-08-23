<?php


namespace App\Service\Expansion;

use App\Models\Expansion;
use Carbon\Carbon;

interface ExpansionServiceInterface
{
    public function getExpansionAt(Carbon $carbon): ?Expansion;

    public function getCurrentExpansion(): Expansion;
}