<?php


namespace App\Service\Expansion;

use App\Models\Expansion;
use Carbon\Carbon;

class ExpansionService implements ExpansionServiceInterface
{

    public function getExpansionAt(Carbon $carbon): ?Expansion
    {
        return Expansion::where('released_at', '<', $carbon->toDateTimeString())->orderBy('id', 'desc')->limit(1)->get()->first();
    }

    public function getCurrentExpansion(): Expansion
    {
        return $this->getExpansionAt(Carbon::now());
    }


}