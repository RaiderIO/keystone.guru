<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Illuminate\Support\Collection;

class CreateRouteBody
{
    public CreateRouteChallengeMode $challengeMode;

    /** @var Collection|CreateRouteNpc */
    public Collection $npcs;

    public function __construct(CreateRouteChallengeMode $createRouteChallengeMode, Collection $npcs)
    {
        $this->challengeMode = $createRouteChallengeMode;
        $this->npcs          = $npcs;
    }
}
