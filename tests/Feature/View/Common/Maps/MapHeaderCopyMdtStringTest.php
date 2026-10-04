<?php

namespace Tests\Feature\View\Common\Maps;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * The route page header offers "Copy MDT string" next to Share, but only where the share modal would offer the
 * MDT export too.
 */
#[Group('View')]
#[Group('MdtExport')]
final class MapHeaderCopyMdtStringTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string BUTTON      = 'id="copy_mdt_string_button"';
    private const string INLINE_CODE = "'common/maps/copymdtstring'";
    private const string SHARE       = 'data-bs-target="#share_modal"';
    private const string OPTIONS     = '{"buttonSelector":".copy_mdt_string_button","shareModalSelector":"#share_modal","edit":%s}';

    #[Test]
    public function view_givenMdtSupportedDungeon_rendersCopyMdtStringButton(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, mdtSupported: true);

        try {
            // Act
            $response = $this->followingRedirects()->get($this->routeUrl('dungeonroute.view', $route));

            // Assert
            $response->assertOk();
            $response->assertSee(self::BUTTON, false);
            $response->assertSee(self::INLINE_CODE, false);
            $response->assertSee(sprintf(self::OPTIONS, 'false'), false);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function view_givenDungeonWithoutMdtSupport_rendersShareWithoutCopyMdtStringButton(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, mdtSupported: false);

        try {
            // Act
            $response = $this->followingRedirects()->get($this->routeUrl('dungeonroute.view', $route));

            // Assert
            $response->assertOk();
            $response->assertSee(self::SHARE, false);
            $response->assertDontSee(self::BUTTON, false);
            $response->assertDontSee(self::INLINE_CODE, false);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * The editor copies the route as it is right now, so the inline code must know it runs in edit mode.
     */
    #[Test]
    public function edit_givenOwner_rendersCopyMdtStringButtonInEditMode(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, mdtSupported: true);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routeUrl('dungeonroute.edit', $route));

            // Assert
            $response->assertOk();
            $response->assertSee(self::BUTTON, false);
            $response->assertSee(sprintf(self::OPTIONS, 'true'), false);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * A published, non-sandbox route owned by $owner in a dungeon that does (not) support MDT.
     */
    private function createRoute(User $owner, bool $mdtSupported): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            facadeEnabled:       false,
            dungeonActive:       true,
            requireDefaultFloor: true,
            resolve:             static fn(Dungeon $dungeon) => $dungeon->mdt_supported === $mdtSupported ? true : null,
        );

        return DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    private function routeUrl(string $routeName, DungeonRoute $route): string
    {
        return route($routeName, [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
