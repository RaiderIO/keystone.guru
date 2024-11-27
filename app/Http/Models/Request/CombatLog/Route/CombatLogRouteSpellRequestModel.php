<?php

namespace App\Http\Models\Request\CombatLog\Route;

use App\Http\Models\Request\RequestModel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

class CombatLogRouteSpellRequestModel extends RequestModel implements Arrayable
{
    private Carbon $castAtCarbon;

    public function __construct(
        public ?int                             $spellId = null,
        public ?string                          $playerUid = null,
        public ?string                          $castAt = null,
        public ?CombatLogRouteCoordRequestModel $coord = null)
    {
    }

    public function getCastAt(): Carbon
    {
        return $this->castAtCarbon ??
            $this->castAtCarbon = Carbon::createFromFormat(CombatLogRouteRequestModel::DATE_TIME_FORMAT, $this->castAt);
    }
}
