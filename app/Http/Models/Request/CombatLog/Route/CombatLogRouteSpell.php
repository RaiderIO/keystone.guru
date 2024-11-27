<?php

namespace App\Http\Models\Request\CombatLog\Route;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

class CombatLogRouteSpell implements Arrayable
{
    private Carbon $castAtCarbon;

    public function __construct(
        public int                 $spellId,
        public string              $playerUid,
        public string              $castAt,
        public CombatLogRouteCoord $coord)
    {
    }

    public function getCastAt(): Carbon
    {
        return $this->castAtCarbon ??
            $this->castAtCarbon = Carbon::createFromFormat(CombatLogRoute::DATE_TIME_FORMAT, $this->castAt);
    }

    public function toArray(): array
    {
        return [
            'spellId'   => $this->spellId,
            'playerUid' => $this->playerUid,
            'castAt'    => $this->castAt,
            'coord'     => $this->coord->toArray(),
        ];
    }

    public static function createFromArray(array $body): CombatLogRouteSpell
    {
        return new CombatLogRouteSpell(
            $body['spellId'],
            $body['playerUid'],
            $body['castAt'],
            CombatLogRouteCoord::createFromArray($body['coord'])
        );
    }
}
