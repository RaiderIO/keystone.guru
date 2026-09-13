<?php

namespace App\Service\EnemyForces\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class EnemyForcesDb2ServiceLogging extends StructuredLogging implements EnemyForcesDb2ServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function diffEnemyForcesStart(string $product, string $build, int $gameVersionId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function diffEnemyForcesEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function diffEnemyForcesUnknownBuild(string $product): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function diffEnemyForcesNoDungeons(int $gameVersionId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function diffEnemyForcesUnresolvedDungeon(int $dungeonId, string $reason): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function writeEnemyForcesStart(int $dungeonId, int $mappingVersionId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function writeEnemyForcesEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function writeEnemyForcesRequired(int $ourEnemyForcesRequired, int $db2EnemyForcesRequired): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function writeEnemyForcesUpdateNpc(int $npcId, int $ourEnemyForces, int $db2EnemyForces): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function writeEnemyForcesCreateNpc(int $npcId, int $db2EnemyForces): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
