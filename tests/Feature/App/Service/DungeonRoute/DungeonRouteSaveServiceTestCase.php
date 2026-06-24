<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\MapIconRepositoryInterface;
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
        ?MapIconRepositoryInterface              $mapIconRepository = null,
    ): DungeonRouteSaveService {
        return new DungeonRouteSaveService(
            $seasonService ?? $this->createMockPublic(SeasonServiceInterface::class),
            $thumbnailService ?? $this->createMockPublic(ThumbnailServiceInterface::class),
            $log ?? $this->createMockPublic(DungeonRouteSaveServiceLoggingInterface::class),
            $mapIconRepository ?? app(MapIconRepositoryInterface::class),
        );
    }

    /**
     * A thumbnail service that accepts (and reports success for) thumbnail refresh queueing,
     * for tests that don't care about the thumbnail interaction itself.
     *
     * @return MockObject&ThumbnailServiceInterface
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

    /**
     * Creates a newer, isolated mapping version for the dungeon so its seeded mapping version becomes
     * "non-current" (getCurrentMappingVersion() resolves to the newer one).
     */
    protected function createNewerMappingVersion(Dungeon $dungeon, MappingVersion $existing): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => $existing->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existing->version + 1000,
            'enemy_forces_required'           => $existing->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existing->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existing->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existing->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existing->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);
    }

    protected function createDungeonStartMapIcon(int $mappingVersionId, int $floorId, ?string $comment = 'mapping.start.east'): MapIcon
    {
        return MapIcon::create([
            'mapping_version_id' => $mappingVersionId,
            'floor_id'           => $floorId,
            'dungeon_route_id'   => null,
            'team_id'            => null,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START],
            'lat'                => -100.0,
            'lng'                => 100.0,
            'comment'            => $comment,
            'permanent_tooltip'  => false,
            'seasonal_index'     => 0,
        ]);
    }
}
