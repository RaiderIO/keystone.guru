<?php

namespace App\Service\CombatLog\Logging;

use Exception;

interface DungeonRouteBuilderLoggingInterface
{
    public function buildStart(string $toDateTimeString, string $eventName): void;

    public function findFloorByUiMapIdNoFloorFound(Exception $exception, int $uitMapId): void;

    public function buildNoFloorFoundYet(): void;

    public function buildChallengeModeEnded(): void;

    public function buildInCombatWithEnemy(string $guid): void;

    public function buildUnitDiedNoLongerInCombat(string $guid): void;

    public function buildUnitDiedNotInCombat(string $guid): void;

    public function buildCreateNewPull(array $keys): void;

    public function buildEnemyNotFound(int $npcId, float $ingameX, float $ingameY): void;

    public function buildEnemyAttachedToKillZone(int $npcId, float $ingameX, float $ingameY): void;

    public function buildCreateNewFinalPull(array $keys): void;

    public function buildEnd(): void;
}
