<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\DungeonRouteSaveService;
use App\Service\DungeonRoute\Logging\DungeonRouteSaveServiceLoggingInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

abstract class DungeonRouteSaveServiceTestCase extends PublicTestCase
{
    use ProvidesDungeon;

    protected function buildService(
        ?SeasonServiceInterface                  $seasonService = null,
        ?ThumbnailServiceInterface               $thumbnailService = null,
        ?DungeonRouteSaveServiceLoggingInterface $log = null,
    ): DungeonRouteSaveService {
        return new DungeonRouteSaveService(
            $seasonService ?? $this->createMockPublic(SeasonServiceInterface::class),
            $thumbnailService ?? $this->createMockPublic(ThumbnailServiceInterface::class),
            $log ?? $this->createMockPublic(DungeonRouteSaveServiceLoggingInterface::class),
        );
    }

    /**
     * A thumbnail service that accepts (and reports success for) thumbnail refresh queueing,
     * for tests that don't care about the thumbnail interaction itself.
     */
    protected function thumbnailServiceAllowingRefresh(): MockObject
    {
        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('queueThumbnailRefresh')->willReturn(true);

        return $thumbnailService;
    }

    protected function getRetailDungeon(): Dungeon
    {
        return $this->getDungeonWithNonFacadeFloor(fn(Builder $query) => $query->whereNotNull('challenge_mode_id'));
    }

    protected function cleanupRoute(DungeonRoute $route): void
    {
        $route->delete();
    }
}
