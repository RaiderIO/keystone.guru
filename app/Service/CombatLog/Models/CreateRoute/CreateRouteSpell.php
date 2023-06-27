<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Carbon\Carbon;
use DateTime;

class CreateRouteSpell
{
    public int $spellId;

    public string $playerUid;

    public string $castAt;

    public CreateRouteCoord $coord;

    private Carbon $castAtCarbon;

    /**
     * @param int              $spellId
     * @param string           $playerUid
     * @param string           $castAt
     * @param CreateRouteCoord $coord
     */
    public function __construct(int $spellId, string $playerUid, string $castAt, CreateRouteCoord $coord)
    {
        $this->spellId   = $spellId;
        $this->playerUid = $playerUid;
        $this->castAt    = $castAt;
        $this->coord     = $coord;
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
     * @param array $body
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
