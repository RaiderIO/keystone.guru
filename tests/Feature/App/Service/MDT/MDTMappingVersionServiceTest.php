<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Enemy;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Logging\MDTMappingVersionServiceLoggingInterface;
use App\Service\MDT\MDTMappingVersionService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\PublicTestCase;

#[Group('MDTMappingVersionService')]
final class MDTMappingVersionServiceTest extends PublicTestCase
{
    use GeneratesDungeonRoutes;

    private const int MISSING_MDT_CLONE_INDEX = 9999;

    #[Test]
    public function getFloorAccuracy_givenEnemyWithoutMatchingMdtClone_logsNoCloneFound(): void
    {
        $dungeonRoute  = null;
        $enemy         = null;
        $originalMdtId = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();

            /** @var Enemy $enemy */
            $enemy         = $this->getSafeMdtEnemies($dungeonRoute)->firstOrFail()->loadMissing('floor');
            $originalMdtId = $enemy->mdt_id;
            Enemy::query()->whereKey($enemy->id)->update(['mdt_id' => self::MISSING_MDT_CLONE_INDEX]);

            // Seeded enemies elsewhere on the floor can miss their MDT clone too, so record every call
            $loggedMissingClones = [];
            $log                 = $this->createMock(MDTMappingVersionServiceLoggingInterface::class);
            $log->method('getFloorAccuracyNoCloneFound')->willReturnCallback(
                static function (int $mdtNpcId, int $cloneIndex) use (&$loggedMissingClones): void {
                    $loggedMissingClones[] = [$mdtNpcId, $cloneIndex];
                },
            );

            $service = new MDTMappingVersionService(
                app(CacheServiceInterface::class),
                app(CoordinatesServiceInterface::class),
                $log,
            );

            // Act
            $service->getFloorAccuracy($dungeonRoute->mappingVersion, $enemy->floor);

            // Assert
            $this->assertContains([$enemy->getMdtNpcId(), self::MISSING_MDT_CLONE_INDEX], $loggedMissingClones);
        } finally {
            if ($originalMdtId !== null) {
                Enemy::query()->whereKey($enemy->id)->update(['mdt_id' => $originalMdtId]);
            }
            $dungeonRoute?->delete();
        }
    }
}
