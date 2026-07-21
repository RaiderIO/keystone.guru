<?php

namespace Tests\Feature\Controller\DungeonRouteController;

use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('Controller')]
#[Group('DungeonRoute')]
#[Group('Throttle')]
final class DungeonRouteControllerThrottleTest extends DungeonRouteControllerCreateTestBase
{
    #[Test]
    public function saveNewTemporary_givenTooManyRequests_isRateLimited(): void
    {
        // Arrange
        $dungeon = $this->getActiveDungeon();
        $created = [];
        // Drive the create-dungeonroute limiter down to one/hour so a couple of requests prove the
        // throttle middleware and limiter registration are wired up, without creating a hundred rows
        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $lastResponse = null;
            for ($i = 0; $i < 3; ++$i) {
                $sinceId      = (int)DungeonRoute::query()->max('id');
                $lastResponse = $this->post(route('dungeonroute.temporary.savenew'), [
                    'dungeon_id' => $dungeon->id,
                ]);

                $route = $this->latestRouteSince($sinceId);
                if ($route !== null) {
                    $created[] = $route;
                }

                if ($lastResponse->status() === 429) {
                    break;
                }
            }

            // Assert
            $this->assertSame(429, $lastResponse->status());
        } finally {
            $this->overrideHttpRateLimit(null);
            foreach ($created as $route) {
                $route->delete();
            }
        }
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        $property = new \ReflectionProperty(\App\Providers\AppServiceProvider::class, 'rateLimitOverrideHttp');
        $property->setValue(null, $limit);
    }
}
