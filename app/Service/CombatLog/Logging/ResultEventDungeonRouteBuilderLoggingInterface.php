<?php

namespace App\Service\CombatLog\Logging;

interface ResultEventDungeonRouteBuilderLoggingInterface
{
    public function buildStart(string $toDateTimeString, string $eventName): void;

    public function buildNoFloorFoundYet(): void;

    public function buildChallengeModeEnded(): void;

    public function buildInCombatWithEnemy(string $guid): void;

    public function buildUnitDiedNoLongerInCombat(string $guid): void;

    public function buildUnitDiedNotInCombat(string $guid): void;

    public function buildCreateNewPull(array $keys): void;

    public function buildCreateNewFinalPull(array $keys): void;

    public function buildEnd(): void;
}
