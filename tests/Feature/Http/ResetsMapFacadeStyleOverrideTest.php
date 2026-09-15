<?php

namespace Tests\Feature\Http;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('ResetsMapFacadeStyleOverride')]
final class ResetsMapFacadeStyleOverrideTest extends PublicTestCase
{
    /**
     * Guards #3917: User::forceMapFacadeStyle() writes a static, so on Octane/Swoole the value the
     * embed, dungeon explore and heatmap controllers set survives until the worker is recycled and
     * silently decides the map facade style of every later request that worker handles.
     */
    #[Test]
    public function handle_givenAMapFacadeStyleOverrideLeftBehindByAnEarlierRequest_clearsItBeforeHandlingTheNextRequest(): void
    {
        // Arrange - the worker state a /embed?mapFacadeStyle=split_floors request leaves behind
        User::forceMapFacadeStyle(User::MAP_FACADE_STYLE_SPLIT_FLOORS);

        try {
            // Act - any subsequent request, by any visitor, on that same worker
            $this->get(route('home'))->assertSuccessful();

            // Assert - the next request resolves the style from the visitor again, not from the override
            self::assertSame(User::DEFAULT_MAP_FACADE_STYLE, User::getCurrentUserMapFacadeStyle());
        } finally {
            User::forceMapFacadeStyle(null);
        }
    }
}
