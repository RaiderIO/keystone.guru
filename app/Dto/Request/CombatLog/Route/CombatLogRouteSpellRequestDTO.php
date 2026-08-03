<?php

namespace App\Dto\Request\CombatLog\Route;

use App\Dto\Request\RequestDTO;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * @OA\Schema(schema="CombatLogRouteSpell")
 * @OA\Property(property="spellId",type="integer")
 * @OA\Property(property="playerUid",type="string")
 * @OA\Property(property="castAt",type="string",format="date-time")
 * @OA\Property(property="coord",type="object",ref="#/components/schemas/CombatLogRouteCoord")
 * @implements Arrayable<string, mixed>
 */
class CombatLogRouteSpellRequestDTO extends RequestDTO implements Arrayable
{
    private Carbon $castAtCarbon;

    public function __construct(
        public ?int                           $spellId = null,
        public ?string                        $playerUid = null,
        public ?string                        $castAt = null,
        public ?CombatLogRouteCoordRequestDTO $coord = null,
    ) {
    }

    public function getCastAt(): Carbon
    {
        return $this->castAtCarbon ??
            $this->castAtCarbon = Carbon::createFromFormat(CombatLogRouteRequestDTO::DATE_TIME_FORMAT, $this->castAt);
    }
}
