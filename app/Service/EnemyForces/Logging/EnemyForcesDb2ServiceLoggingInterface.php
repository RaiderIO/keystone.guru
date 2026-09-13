<?php

namespace App\Service\EnemyForces\Logging;

interface EnemyForcesDb2ServiceLoggingInterface
{
    public function diffEnemyForcesStart(string $product, string $build, int $gameVersionId): void;

    public function diffEnemyForcesEnd(): void;

    public function diffEnemyForcesUnknownBuild(string $product): void;

    public function diffEnemyForcesNoDungeons(int $gameVersionId): void;

    public function diffEnemyForcesUnresolvedDungeon(int $dungeonId, string $reason): void;

    public function writeEnemyForcesStart(int $dungeonId, int $mappingVersionId): void;

    public function writeEnemyForcesEnd(): void;

    public function writeEnemyForcesRequired(int $ourEnemyForcesRequired, int $db2EnemyForcesRequired): void;

    public function writeEnemyForcesUpdateNpc(int $npcId, int $ourEnemyForces, int $db2EnemyForces): void;

    public function writeEnemyForcesCreateNpc(int $npcId, int $db2EnemyForces): void;
}
