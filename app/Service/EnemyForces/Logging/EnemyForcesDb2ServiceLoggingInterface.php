<?php

namespace App\Service\EnemyForces\Logging;

interface EnemyForcesDb2ServiceLoggingInterface
{
    public function diffEnemyForcesStart(string $product, string $build, int $gameVersionId): void;

    public function diffEnemyForcesEnd(): void;

    public function diffEnemyForcesUnknownBuild(string $product): void;

    public function diffEnemyForcesNoDungeons(int $gameVersionId): void;

    public function diffEnemyForcesUnresolvedDungeon(int $dungeonId, string $reason): void;
}
