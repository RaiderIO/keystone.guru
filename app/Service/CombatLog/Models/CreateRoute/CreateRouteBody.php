<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Illuminate\Support\Collection;

class CreateRouteBody
{
    public CreateRouteChallengeMode $challengeMode;

    /** @var Collection|CreateRouteNpc[] */
    public Collection $npcs;

    public function __construct(CreateRouteChallengeMode $createRouteChallengeMode, Collection $npcs)
    {
        $this->challengeMode = $createRouteChallengeMode;
        $this->npcs          = $npcs;
    }

    /**
     * @param array $body
     * @return CreateRouteBody
     */
    public static function createFromArray(array $body): CreateRouteBody
    {
        $challengeMode = CreateRouteChallengeMode::createFromArray($body['challengeMode']);

        $npcs = collect();
        foreach($body['npcs'] as $npc){
            $npcs->push(CreateRouteNpc::createFromArray($npc));
        }

        return new CreateRouteBody(
            $challengeMode,
            $npcs
        );
    }
}
