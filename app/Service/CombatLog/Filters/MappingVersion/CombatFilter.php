<?php

namespace App\Service\CombatLog\Filters\MappingVersion;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedData;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Service\CombatLog\Filters\BaseCombatFilter;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\Logging\BaseCombatFilterLoggingInterface;
use App\Service\CombatLog\Logging\MappingVersionCombatFilterLoggingInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class CombatFilter extends BaseCombatFilter
{
    /** @var bool */
    private bool $zoneFound = false;

    /** @var MappingVersionCombatFilterLoggingInterface */
    private MappingVersionCombatFilterLoggingInterface $log;

    public function __construct(Collection $resultEvents)
    {
        parent::__construct($resultEvents);

        /** @var MappingVersionCombatFilterLoggingInterface $log */
        $log       = App::make(MappingVersionCombatFilterLoggingInterface::class);
        $this->log = $log;
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @param int       $lineNr
     *
     * @return bool
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        // First, we wait for the challenge mode to start
        if ($combatLogEvent instanceof ZoneChange) {
            $this->log->parseZoneChangeFound($lineNr);
            $this->zoneFound   = true;

            return false;
        }

        // If it hasn't started yet, we don't process anything
        if (!$this->zoneFound) {
            return false;
        }

        return parent::parse($combatLogEvent, $lineNr);
    }
}
