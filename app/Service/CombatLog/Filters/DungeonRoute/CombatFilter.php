<?php

namespace App\Service\CombatLog\Filters\DungeonRoute;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Service\CombatLog\Filters\BaseCombatFilter;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\Logging\DungeonRouteCombatFilterLoggingInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class CombatFilter extends BaseCombatFilter implements CombatLogParserInterface
{
    private bool $challengeModeStarted = false;

    private readonly DungeonRouteCombatFilterLoggingInterface $log;

    public function __construct(Collection $resultEvents)
    {
        parent::__construct($resultEvents);

        /** @var DungeonRouteCombatFilterLoggingInterface $log */
        $log = App::make(DungeonRouteCombatFilterLoggingInterface::class);
        $this->log = $log;
    }

    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        // First, we wait for the challenge mode to start
        if ($combatLogEvent instanceof ChallengeModeStart) {
            $this->log->parseChallengeModeStartFound($lineNr);
            $this->challengeModeStarted = true;

            return false;
        }

        // If it hasn't started yet, we don't process anything
        if (! $this->challengeModeStarted) {
            return false;
        }

        return parent::parse($combatLogEvent, $lineNr);
    }
}
