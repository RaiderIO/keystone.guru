<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Arrow;
use App\Models\KillZone\KillZone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

/**
 * Per-route object caps used to be DungeonRoutePolicy::addKillZone/addArrow/..., which answered a
 * quota breach with a 403. They are now 422s: the request is understood and allowed, there is just
 * no room left.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteLimitsTest extends DungeonRouteTestBase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    #[Test]
    public function store_givenKillZoneLimitReached_returnsUnprocessableEntity(): void
    {
        // Arrange - drop the cap to 1 and fill it, rather than creating 50 rows
        config(['keystoneguru.dungeon_route_limits.kill_zones' => 1]);
        $this->dungeonRoute->killZones()->delete();
        KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => null,
            'lat'              => null,
            'lng'              => null,
            'color'            => '#000000',
            'index'            => 1,
        ]);

        try {
            // Act
            $response = $this->withHeader('Accept', 'application/json')
                ->post(sprintf('/ajax/%s/killzone', $this->dungeonRoute->public_key), [
                    'color'   => '#ff0000',
                    'index'   => 2,
                    'enemies' => [],
                    'spells'  => [],
                ]);

            // Assert - a 422, and specifically not the 404 the surrounding catch would produce
            $response->assertStatus(422);
            // The map editor shows xhr.responseJSON.message verbatim, so the specific limit text
            // must survive the abort - a bare "Unprocessable Content" would be a UX regression
            $response->assertJsonPath('message', __('policy.add_kill_zone_limit_reached', ['limit' => 1]));
            $this->assertSame(1, $this->dungeonRoute->killZones()->count());
        } finally {
            $this->dungeonRoute->killZones()->delete();
        }
    }

    #[Test]
    public function store_givenKillZoneLimitNotReached_succeeds(): void
    {
        // Arrange
        config(['keystoneguru.dungeon_route_limits.kill_zones' => 5]);
        $this->dungeonRoute->killZones()->delete();

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/killzone', $this->dungeonRoute->public_key), [
                'color'   => '#ff0000',
                'index'   => 1,
                'enemies' => [],
                'spells'  => [],
            ]);

            // Assert
            $response->assertSuccessful();
        } finally {
            $this->dungeonRoute->killZones()->delete();
        }
    }

    #[Test]
    public function store_givenArrowLimitReached_returnsUnprocessableEntity(): void
    {
        // Arrange
        config(['keystoneguru.dungeon_route_limits.arrows' => 0]);

        // Act
        $response = $this->post(sprintf('/ajax/%s/arrow', $this->dungeonRoute->public_key), [
            'floor_id' => $this->dungeonRoute->dungeon->floors->first()->id,
            'polyline' => [
                'color'          => '#ff0000',
                'color_animated' => null,
                'weight'         => 3,
                'vertices_json'  => '[{"lat":-100,"lng":100},{"lat":-120,"lng":120}]',
            ],
        ]);

        // Assert
        $response->assertStatus(422);
        $this->assertSame(0, Arrow::where('dungeon_route_id', $this->dungeonRoute->id)->count());
    }
}
