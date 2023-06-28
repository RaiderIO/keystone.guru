<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Illuminate\Support\Collection;

class CreateRouteBody
{
//    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:sP';
    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    public CreateRouteChallengeMode $challengeMode;

    /** @var Collection|CreateRouteNpc[] */
    public Collection $npcs;

    /** @var Collection|CreateRouteSpell[] */
    public Collection $spells;

    public function __construct(CreateRouteChallengeMode $createRouteChallengeMode, Collection $npcs, Collection $spells)
    {
        $this->challengeMode = $createRouteChallengeMode;
        $this->npcs          = $npcs;
        $this->spells        = $spells;
    }

    /**
     * @param array $body
     * @return CreateRouteBody
     */
    public static function createFromArray(array $body): CreateRouteBody
    {
        $challengeMode = CreateRouteChallengeMode::createFromArray($body['challengeMode']);

        $npcs = collect();
        foreach ($body['npcs'] as $npc) {
            $npcs->push(CreateRouteNpc::createFromArray($npc));
        }

        $spells = collect();
        foreach ($body['spells'] ?? [] as $spell) {
            $spells->push(CreateRouteSpell::createFromArray($spell));
        }

        return new CreateRouteBody(
            $challengeMode,
            $npcs,
            $spells
        );
    }
}
