<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\CastSuccess;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\ResultEvents\SpellCast;
use Illuminate\Support\Collection;

class SpellFilter implements CombatLogParserInterface
{
    private const SPELLS_TO_TRACK = [
        // Shaman
        4725, // Bloodlust

        // Mage
        80353, // Time Warp

        // Evoker
        390386, // Fury of the Aspects

        // Hunter pets
        90355, // Ancient Hysteria
    ];

    private Collection $resultEvents;

    /**
     * @param Collection $resultEvents
     */
    public function __construct(Collection $resultEvents)
    {
        $this->resultEvents = $resultEvents;
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @param int       $lineNr
     * @return bool
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
            return false;
        }

        if (!($combatLogEvent->getPrefix() instanceof Spell) || !($combatLogEvent->getSuffix() instanceof CastSuccess)) {
            return false;
        }

        /** @var Spell $prefix */
        $prefix = $combatLogEvent->getPrefix();

        $isValidSpell = in_array($prefix->getSpellId(), self::SPELLS_TO_TRACK);
        if ($isValidSpell) {
            $this->resultEvents->push(new SpellCast($combatLogEvent));
        }

        return $isValidSpell;
    }
}
