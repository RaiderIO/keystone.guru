<?php

namespace App\Http\RequestModels\Api\V1;

use Illuminate\Support\Collection;

class CreateRoute
{
    public CreateRouteChallengeMode $challengeMode;

    /** @var Collection|CreateRouteNpc  */
    public Collection $npcs;
}
