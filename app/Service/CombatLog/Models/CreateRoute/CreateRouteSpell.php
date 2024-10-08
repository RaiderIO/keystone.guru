<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

class CreateRouteSpell implements Arrayable
{
    private Carbon $castAtCarbon;

    public function __construct(
        public int              $spellId,
        public string           $playerUid,
        public string           $castAt,
        public CreateRouteCoord $coord)
    {
    }

    public function getCastAt(): Carbon
    {
        return $this->castAtCarbon ??
            $this->castAtCarbon = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $this->castAt);
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

    public static function createFromArray(array $body): CreateRouteSpell
    {
        return new CreateRouteSpell(
            $body['spellId'],
            $body['playerUid'],
            $body['castAt'],
            CreateRouteCoord::createFromArray($body['coord'])
        );
    }
}
