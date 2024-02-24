<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Carbon\Carbon;
use DateTime;

class CreateRouteSpell
{
    private Carbon $castAtCarbon;

    public function __construct(public int $spellId, public string $playerUid, public string $castAt, public CreateRouteCoord $coord)
    {
    }


    /**
     * @return Carbon
     */
    public function getCastAt(): Carbon
    {
        return $this->castAtCarbon ??
            $this->castAtCarbon = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $this->castAt);
    }

    /**
     * @return CreateRouteSpell
     */
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
