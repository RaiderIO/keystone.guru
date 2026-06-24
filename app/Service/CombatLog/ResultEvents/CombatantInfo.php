<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\CombatantInfoInterface as CombatantInfoEvent;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;

class CombatantInfo extends BaseResultEvent
{
    private CharacterClassSpecialization $characterClassSpecialization;

    public function __construct(CombatantInfoEvent&BaseEvent $combatantInfoEvent)
    {
        parent::__construct($combatantInfoEvent);
    }

    public function getCombatantInfoEvent(): CombatantInfoEvent
    {
        $baseEvent = $this->getBaseEvent();
        assert($baseEvent instanceof CombatantInfoEvent);

        return $baseEvent;
    }

    public function getGuid(): Player
    {
        return $this->getCombatantInfoEvent()->getPlayerGuid();
    }

    public function getClass(): CharacterClass
    {
        return $this->getSpecialization()->class;
    }

    public function getSpecialization(): CharacterClassSpecialization
    {
        if (!isset($this->characterClassSpecialization)) {
            $this->characterClassSpecialization = CharacterClassSpecialization
                ::where('specialization_id', $this->getCombatantInfoEvent()->getCurrentSpecId())
                    ->with('class')
                    ->firstOrFail();
        }

        return $this->characterClassSpecialization;
    }
}
