<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Illuminate\Support\Collection;

class CreateRouteBody
{
    //    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:sP';
    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    public CreateRouteMetadata $metadata;

    public CreateRouteSettings $settings;

    public CreateRouteChallengeMode $challengeMode;

    /** @var Collection|CreateRouteNpc[] */
    public Collection $npcs;

    /** @var Collection|CreateRouteSpell[] */
    public Collection $spells;

    public function __construct(
        CreateRouteMetadata      $metadata,
        CreateRouteSettings      $settings,
        CreateRouteChallengeMode $challengeMode,
        Collection               $npcs,
        Collection               $spells
    )
    {
        $this->metadata      = $metadata;
        $this->settings      = $settings;
        $this->challengeMode = $challengeMode;
        $this->npcs          = $npcs;
        $this->spells        = $spells;
    }

    /**
     * @param array $body
     *
     * @return CreateRouteBody
     */
    public static function createFromArray(array $body): CreateRouteBody
    {
        $metadata      = CreateRouteMetadata::createFromArray($body['metadata'] ?? []);
        $settings      = CreateRouteSettings::createFromArray($body['settings'] ?? []);
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
            $metadata,
            $settings,
            $challengeMode,
            $npcs,
            $spells
        );
    }
}
