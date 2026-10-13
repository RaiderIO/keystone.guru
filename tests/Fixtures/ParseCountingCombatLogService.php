<?php

namespace Tests\Fixtures;

use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\CombatLog\CombatLogService;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use ArrayObject;

/**
 * A real CombatLogService that appends the path of every parseCombatLog() call - including the ones it makes to
 * itself, such as from getChallengeModes() - to $parsedFilePaths.
 */
readonly class ParseCountingCombatLogService extends CombatLogService
{
    /**
     * @param ArrayObject<int, string> $parsedFilePaths
     */
    public function __construct(
        private ArrayObject              $parsedFilePaths,
        SeasonServiceInterface           $seasonService,
        SeasonAffixGroupServiceInterface $seasonAffixGroupService,
        NpcRepositoryInterface           $npcRepository,
        DungeonRepositoryInterface       $dungeonRepository,
        CombatLogServiceLoggingInterface $log,
    ) {
        parent::__construct($seasonService, $seasonAffixGroupService, $npcRepository, $dungeonRepository, $log);
    }

    public function parseCombatLog(string $filePath, callable $callback): void
    {
        $this->parsedFilePaths->append($filePath);

        parent::parseCombatLog($filePath, $callback);
    }
}
