<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Team;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRouteService')]
final class DungeonRouteServiceTouchRoutesForTeamTest extends PublicTestCase
{
    #[Test]
    public function touchRoutesForTeam_givenRouteWithFreshThumbnail_keepsThumbnailFresh(): void
    {
        // Arrange
        $teamId       = $this->getUnusedTeamId();
        $dungeonRoute = null;

        try {
            $dungeonRoute = $this->createTeamRoute($teamId, now()->subDays(2), now()->subDay());

            // Act
            $updatedRouteCount = app(DungeonRouteServiceInterface::class)->touchRoutesForTeam($teamId);

            // Assert
            $dungeonRoute->refresh();
            $this->assertSame(1, $updatedRouteCount);
            $this->assertTrue($dungeonRoute->updated_at->isAfter(now()->subMinute()), 'The touch must still bump updated_at');
            $this->assertTrue($dungeonRoute->thumbnail_updated_at->greaterThanOrEqualTo($dungeonRoute->updated_at));
            $this->assertTrue(
                app(DungeonRouteRepositoryInterface::class)
                    ->getDungeonRoutesWithExpiredThumbnails(collect([$dungeonRoute]))
                    ->isEmpty(),
                'A touched route with a fresh thumbnail must not be queued for a re-render',
            );
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function touchRoutesForTeam_givenRouteWithStaleThumbnail_leavesThumbnailStale(): void
    {
        // Arrange
        $teamId                  = $this->getUnusedTeamId();
        $dungeonRoute            = null;
        $staleThumbnailUpdatedAt = now()->subDays(2)->startOfSecond();

        try {
            $dungeonRoute = $this->createTeamRoute($teamId, now()->subDay(), $staleThumbnailUpdatedAt);

            // Act
            app(DungeonRouteServiceInterface::class)->touchRoutesForTeam($teamId);

            // Assert
            $dungeonRoute->refresh();
            $this->assertTrue($dungeonRoute->updated_at->isAfter(now()->subMinute()), 'The touch must still bump updated_at');
            $this->assertTrue($dungeonRoute->thumbnail_updated_at->equalTo($staleThumbnailUpdatedAt));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function touchRoutesForTeam_givenRouteOfAnotherTeam_leavesRouteUntouched(): void
    {
        // Arrange
        $teamId       = $this->getUnusedTeamId();
        $dungeonRoute = null;
        $updatedAt    = now()->subDays(2)->startOfSecond();

        try {
            $dungeonRoute = $this->createTeamRoute($teamId + 1, $updatedAt, now()->subDay());

            // Act
            $updatedRouteCount = app(DungeonRouteServiceInterface::class)->touchRoutesForTeam($teamId);

            // Assert
            $this->assertSame(0, $updatedRouteCount);
            $this->assertTrue($dungeonRoute->refresh()->updated_at->equalTo($updatedAt));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    private function getUnusedTeamId(): int
    {
        return (int)Team::query()->max('id') + 1000;
    }

    private function createTeamRoute(int $teamId, Carbon $updatedAt, Carbon $thumbnailUpdatedAt): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'  => 1,
            'expires_at' => null,
        ]);

        // Written through the query builder so Eloquent does not overwrite updated_at
        DungeonRoute::query()->whereKey($dungeonRoute->id)->toBase()->update([
            'team_id'              => $teamId,
            'updated_at'           => $updatedAt->toDateTimeString(),
            'thumbnail_updated_at' => $thumbnailUpdatedAt->toDateTimeString(),
        ]);

        return $dungeonRoute->refresh();
    }
}
