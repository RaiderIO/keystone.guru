<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell as SpellPrefix;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\EncounterEndInterface;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\Spell\Spell;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;

/**
 * Checks if we need to keep a combat log event based on whether a unit has been defeated or not.
 */
class UnitDefeatedFilter implements CombatLogParserInterface
{
    /** @var float[] The percentage (between 0 and 1) when certain enemies are considered defeated */
    private const array DEFEATED_PERCENTAGE = [
        // Grim Batol: Valiona is defeated at 50%
        40320 => 0.51,

        // Siege of Boralus: Viq'Goth flails around, disappears in the deep at 1 hp and leaves a chest
        128652 => 0.01,

        // Mists of Tirna Scithe: Tirnenn Villager is defeated at like 20%?
        164929 => 0.20,
        // Mistveil Defenders just despawn when defeated
        163058 => 0.01,
        171772 => 0.01,

        // Uldaman: Lost Dwarves, they kinda stop fighting when they reach below 10%
        184580 => 0.1,
        184581 => 0.1,
        184582 => 0.1,

        // Uldaman: Chrono-Lord Deios goes "ENOUGH" at 1hp, makes himself immune and teleports away
        184125 => 0.01,

        // Brackenhide Hollow: Decatriarch Wratheye is defeated at 5%
        186121 => 0.05,

        // DOTI: Galakrond's Fall: Iridikron gets zapped at 85%
        198933 => 0.85,

        // City of Threads: Xeph'itik is defeated at 50%
        219984 => 0.51,

        // Darkflame Cleft: The Darkness is defeated at 45%
        208747 => 0.46,

        // Mogu'shan Palace: Trial of the King
        61442 => 0.01,
        61444 => 0.01,
        61445 => 0.01,

        // Shado-Pan Monastery:
        // Shado-pan Desciples become friendly at 1hp
        58198 => 0.01,
        // Flying Snow becomes friendly at 1hp
        56473 => 0.01,
        // Fragrant Lotus becomes friendly at 1hp
        56472 => 0.01,
        // Master Snowdrift becomes friendly at 1hp
        56541 => 0.01,
        // Corrupted Taran Zhu despawns and becomes friendly Taran Zhu at 1hp
        56884 => 0.01,

        // Scholomance
        // Lilian Voss
        58722 => 0.01,

        // Tazavesh: Streets of Wonder
        // Achillite is never defeated, but starts pumping out lightning balls at 1hp instead
        176555 => 0.01,

        // The Dawnbreaker
        // Rasha'nan is defeated at 60%
        213937 => 0.61,

        // Eco-Dome Al'Dani
        // Soul-Scribe becomes unattackable at 1hp
        234935 => 0.01,

        // Windrunner Spire
        // Emberdawn becomes unattackable at less than 5%
        231606 => 0.05,

        // Maisara Caverns
        // Muro'jin and Nekraxx do die, but one can resurrect the other, so we consider them defeated at less than 2% hp
        247570 => 0.02,
        247572 => 0.02,
    ];

    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        return $combatLogEvent instanceof UnitDied || $this->isEnemyDefeated($combatLogEvent) || $this->hasDeathAuraApplied($combatLogEvent);
    }

    public function isPlayerDeathEntry(BaseEvent $combatLogEvent): bool
    {
        if (!($combatLogEvent instanceof UnitDied)) {
            return false;
        }

        return $combatLogEvent->getGenericData()->getDestGuid() instanceof Player;
    }

    public function hasDeathAuraApplied(BaseEvent $combatLogEvent): bool
    {
        if (!($combatLogEvent instanceof CombatLogEvent)) {
            return false;
        }
        if (!($combatLogEvent->getSuffix() instanceof AuraAppliedInterface)) {
            return false;
        }
        $prefix = $combatLogEvent->getPrefix();
        if (!($prefix instanceof SpellPrefix)) {
            return false;
        }

        return in_array($prefix->getSpellId(), Spell::CHARM_SPELLS);
    }

    public function isEnemyDefeated(BaseEvent $combatLogEvent): bool
    {
        // If an encounter was ended, then yes this enemy was defeated
        if ($combatLogEvent instanceof EncounterEndInterface) {
            return true;
        }

        if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
            return false;
        }

        $destGuid = $combatLogEvent->getGenericData()->getDestGuid();
        if (!($destGuid instanceof Creature)) {
            return false;
        }

        if (!isset(self::DEFEATED_PERCENTAGE[$destGuid->getId()])) {
            return false;
        }

        $advancedData = $combatLogEvent->getAdvancedData();

        // Assume the enemy is immortal? (e.g., a boss that doesn't have a max HP)
        if ($advancedData->getMaxHP() === 0) {
            return false;
        }

        return ($advancedData->getCurrentHP() / $advancedData->getMaxHP()) <= self::DEFEATED_PERCENTAGE[$destGuid->getId()];
    }
}
