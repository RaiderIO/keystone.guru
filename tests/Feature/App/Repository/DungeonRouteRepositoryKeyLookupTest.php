<?php

namespace Tests\Feature\App\Repository;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRouteRepository')]
final class DungeonRouteRepositoryKeyLookupTest extends PublicTestCase
{
    #[Test]
    public function findByIdOrPublicKeyOrFail_givenNumericKey_findsTheRouteById(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = DungeonRoute::factory()->create(['author_id' => $owner->id]);

        try {
            // Act
            $result = app(DungeonRouteRepositoryInterface::class)->findByIdOrPublicKeyOrFail((string)$route->id);

            // Assert
            $this->assertSame($route->id, $result->id);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function findByIdOrPublicKeyOrFail_givenPublicKey_findsTheRouteByPublicKey(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = DungeonRoute::factory()->create(['author_id' => $owner->id]);

        try {
            // Act
            $result = app(DungeonRouteRepositoryInterface::class)->findByIdOrPublicKeyOrFail($route->public_key);

            // Assert
            $this->assertSame($route->id, $result->id);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function findByIdOrPublicKeyOrFail_givenUnknownKey_throwsModelNotFoundException(): void
    {
        // Assert
        $this->expectException(ModelNotFoundException::class);

        // Act
        app(DungeonRouteRepositoryInterface::class)->findByIdOrPublicKeyOrFail('zzzzzzzz');
    }
}
