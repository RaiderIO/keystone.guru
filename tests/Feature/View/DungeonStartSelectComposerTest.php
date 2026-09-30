<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\DungeonStartSelectComposer;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonStart;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('ViewComposers')]
#[Group('DungeonStart')]
final class DungeonStartSelectComposerTest extends PublicTestCase
{
    #[Test]
    public function compose_givenRouteOnMappingVersionWithTwoStarts_offersTheRouteMappingVersionStarts(): void
    {
        // Arrange
        $mappingVersion = $this->mappingVersionWithoutDungeonStarts();
        $floorId        = $mappingVersion->dungeon->floors()->value('id');

        $eastStart = DungeonStart::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floorId,
            'comment'            => 'East entrance',
        ]);
        $westStart = DungeonStart::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floorId,
            'comment'            => null,
        ]);

        $view = view('common.dungeonroute.create.dungeonstartselect', [
            'dungeonroute' => $this->unsavedRouteOn($mappingVersion),
        ]);

        try {
            // Act
            app(DungeonStartSelectComposer::class)->compose($view);

            // Assert
            $this->assertSame([
                ['id' => $eastStart->id, 'text' => 'East entrance'],
                ['id' => $westStart->id, 'text' => sprintf('%s #2', __('mapicontypes.dungeon_start'))],
            ], $view->getData()['dungeonStartsByDungeonId']->get($mappingVersion->dungeon_id)->all());
        } finally {
            $eastStart->delete();
            $westStart->delete();
        }
    }

    #[Test]
    public function compose_givenRouteOnMappingVersionWithOneStart_doesNotOfferAChoice(): void
    {
        // Arrange
        $mappingVersion = $this->mappingVersionWithoutDungeonStarts();

        $onlyStart = DungeonStart::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors()->value('id'),
        ]);

        $view = view('common.dungeonroute.create.dungeonstartselect', [
            'dungeonroute' => $this->unsavedRouteOn($mappingVersion),
        ]);

        try {
            // Act
            app(DungeonStartSelectComposer::class)->compose($view);

            // Assert
            $offeredStartIds = $view->getData()['dungeonStartsByDungeonId']
                ->get($mappingVersion->dungeon_id, collect())
                ->pluck('id');
            $this->assertNotContains($onlyStart->id, $offeredStartIds);
        } finally {
            $onlyStart->delete();
        }
    }

    private function mappingVersionWithoutDungeonStarts(): MappingVersion
    {
        $mappingVersion = MappingVersion::query()
            ->whereDoesntHave('dungeonStarts')
            ->whereHas('dungeon.floors')
            ->first();

        if ($mappingVersion === null) {
            $this->fail('No seeded mapping version without dungeon starts found.');
        }

        return $mappingVersion;
    }

    private function unsavedRouteOn(MappingVersion $mappingVersion): DungeonRoute
    {
        return DungeonRoute::factory()->make([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
    }
}
