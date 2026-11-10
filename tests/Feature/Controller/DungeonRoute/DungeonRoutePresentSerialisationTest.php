<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRoutePresentSerialisationTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function presentFloor_givenCombatLogRoute_doesNotRenderTheRunData(): void
    {
        // Arrange
        $owner                      = User::factory()->create();
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 1, requireDefaultFloor: true);
        $route                      = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        $challengeModeRun = ChallengeModeRun::factory()->create([
            'dungeon_id'       => $route->dungeon_id,
            'dungeon_route_id' => $route->id,
        ]);
        $postBodyMarker = sprintf('post-body-marker-%s', Str::random(16));
        $runId          = sprintf('run-id-marker-%s', Str::random(16));
        $correlationId  = sprintf('correlation-id-marker-%s', Str::random(16));
        ChallengeModeRunData::create([
            'challenge_mode_run_id' => $challengeModeRun->id,
            'run_id'                => $runId,
            'correlation_id'        => $correlationId,
            'post_body'             => json_encode(['marker' => $postBodyMarker]),
            'processed'             => false,
        ]);
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            // Act
            $response = $this->get(route('dungeonroute.present.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $floor->index,
            ]));

            // Assert
            $response->assertOk();
            $response->assertSee('"dungeonroute":{"affixes":', false);
            $response->assertViewHas(
                'dungeonroute',
                static fn(DungeonRoute $viewRoute) => $viewRoute->challengeModeRun->id === $challengeModeRun->id
                    && !$viewRoute->challengeModeRun->relationLoaded('challengeModeRunData'),
            );
            $response->assertDontSee($postBodyMarker, false);
            $response->assertDontSee($runId, false);
            $response->assertDontSee($correlationId, false);
            $response->assertDontSee('"challenge_mode_run"', false);
        } finally {
            $challengeModeRun->delete();
            $route->delete();
            $owner->delete();
        }
    }
}
