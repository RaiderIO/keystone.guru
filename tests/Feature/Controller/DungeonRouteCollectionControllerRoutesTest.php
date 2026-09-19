<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use Database\Factories\DungeonRoute\DungeonRouteCollectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

/**
 * The route lists of the collection edit page, which save on their own and add routes through the route picker.
 */
#[Group('Controller')]
#[Group('DungeonRouteCollection')]
final class DungeonRouteCollectionControllerRoutesTest extends PublicTestCase
{
    use CreatesSeason;

    private ?User $owner = null;

    /** @var array<int, DungeonRoute> */
    private array $createdDungeonRoutes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->beforeApplicationDestroyed(function (): void {
            if ($this->owner !== null) {
                DungeonRouteCollection::query()
                    ->where('user_id', $this->owner->id)
                    ->get()
                    ->each(static fn(DungeonRouteCollection $dungeonRouteCollection) => $dungeonRouteCollection->delete());
            }

            foreach ($this->createdDungeonRoutes as $dungeonRoute) {
                $dungeonRoute->delete();
            }

            if ($this->owner !== null) {
                Feature::for($this->owner)->forget(CreatorProfiles::class);
                $this->owner->delete();
            }
        });
    }

    #[Test]
    public function edit_givenAFreeFormCollection_offersOneAddButtonAndLocksThePickerToTheGameVersion(): void
    {
        // Arrange
        $owner                  = $this->owner();
        $dungeonRoute           = $this->createRoute($this->retailMappingVersion());
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), [$dungeonRoute]);

        // Act
        $response = $this->actingAs($owner)->get($this->editUrl($dungeonRouteCollection));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        $this->assertSame(1, preg_match_all('/<button id="[^"]*_add_button"/', $content));
        $this->assertStringContainsString('id="dungeon_routes_add_button"', $content);
        $this->assertStringContainsString('id="collection_route_picker"', $content);
        $this->assertStringContainsString(sprintf('"lockedParameters":{"game_version_id":%d}', $this->retail()->id), $content);
        $this->assertStringContainsString(sprintf('"existingPublicKeys":["%s"]', $dungeonRoute->public_key), $content);
        $this->assertStringContainsString(sprintf('"max":%d', DungeonRouteCollection::MAX_ROUTES), $content);
    }

    #[Test]
    public function edit_givenASeasonSet_offersAnAddButtonPerPoolDungeonAndLocksThePickerToTheSeason(): void
    {
        // Arrange
        $owner                  = $this->owner();
        $mappingVersions        = $this->retailMappingVersions()->take(2)->values();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id], $mappingVersions->pluck('dungeon_id')->all());
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season), []);

        // Act
        $response = $this->actingAs($owner)->get($this->editUrl($dungeonRouteCollection));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        foreach ($mappingVersions as $mappingVersion) {
            $this->assertStringContainsString(sprintf('id="dungeon_routes_%d_add_button"', $mappingVersion->dungeon_id), $content);
            $response->assertSeeText(__('view_common.collection.routes.add_route_for', ['dungeon' => __($mappingVersion->dungeon->name)]));
        }
        $this->assertStringContainsString(sprintf(
            '"lockedParameters":{"game_version_id":%d,"season_id":%d,"dungeon_ids":[%s]}',
            $this->retail()->id,
            $season->id,
            $season->dungeons()->pluck('dungeons.id')->implode(','),
        ), $content);
    }

    #[Test]
    public function edit_givenTheOwner_keepsTheRoutesOutOfTheDetailsForm(): void
    {
        // Arrange
        $owner                  = $this->owner();
        $dungeonRoute           = $this->createRoute($this->retailMappingVersion());
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), [$dungeonRoute]);

        // Act
        $response = $this->actingAs($owner)->get($this->editUrl($dungeonRouteCollection));

        // Assert
        $response->assertOk();
        $this->assertSame(1, preg_match('/<form[^>]*method="POST"[^>]*action="[^"]*\/collections\/[^"]*"[^>]*>(.*?)<\/form>/s', (string)$response->getContent(), $form));
        $this->assertStringNotContainsString('dungeon_routes[]', $form[1]);
    }

    #[Test]
    public function edit_givenAnAdminOnSomeoneElsesCollection_offersNoPicker(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        $dungeonRoute           = $this->createRoute($this->retailMappingVersion());
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), [$dungeonRoute]);
        Feature::for($admin)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($admin)->get($this->editUrl($dungeonRouteCollection));

            // Assert
            $response->assertOk();
            $content = (string)$response->getContent();
            $this->assertStringNotContainsString('id="collection_route_picker"', $content);
            $this->assertSame(0, preg_match_all('/<button id="[^"]*_add_button"/', $content));
            $response->assertSeeText(__('view_common.collection.routes.owner_only'));
            $response->assertSeeText($dungeonRoute->title);
        } finally {
            Feature::for($admin)->forget(CreatorProfiles::class);
        }
    }

    #[Test]
    public function update_givenNoRoutesPosted_keepsTheRoutesOfTheCollection(): void
    {
        // Arrange
        $owner                  = $this->owner();
        $dungeonRoute           = $this->createRoute($this->retailMappingVersion());
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), [$dungeonRoute]);

        // Act
        $response = $this->actingAs($owner)->patch(route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]), [
            'name'            => 'ZzTestRenamedCollection',
            'published_state' => PublishedState::WORLD,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $dungeonRouteCollection->refresh();
        $this->assertSame('ZzTestRenamedCollection', $dungeonRouteCollection->name);
        $this->assertSame([$dungeonRoute->id], $dungeonRouteCollection->dungeonRoutes->pluck('id')->all());
    }

    private function owner(): User
    {
        if ($this->owner === null) {
            $this->owner = User::factory()->create();
            $this->owner->addRole(Role::ROLE_USER);
            Feature::for($this->owner)->activate(CreatorProfiles::class);
        }

        return $this->owner;
    }

    private function retail(): GameVersion
    {
        return GameVersion::query()->findOrFail(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]);
    }

    private function retailMappingVersion(): MappingVersion
    {
        return $this->retailMappingVersions()->firstOrFail();
    }

    /**
     * @return Collection<int, MappingVersion>
     */
    private function retailMappingVersions(): Collection
    {
        return MappingVersion::query()
            ->with('dungeon')
            ->where('game_version_id', $this->retail()->id)
            ->whereHas('dungeon', static fn(Builder $query) => $query->whereNotNull('challenge_mode_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('dungeon_id')
            ->values();
    }

    private function createRoute(MappingVersion $mappingVersion): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => $this->owner()->id,
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        $this->createdDungeonRoutes[] = $dungeonRoute;

        return $dungeonRoute;
    }

    /**
     * @param array<int, DungeonRoute> $dungeonRoutes
     */
    private function createCollection(DungeonRouteCollectionFactory $factory, array $dungeonRoutes): DungeonRouteCollection
    {
        $dungeonRouteCollection = $factory->create(['user_id' => $this->owner()->id]);

        foreach ($dungeonRoutes as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }

        return $dungeonRouteCollection;
    }

    private function editUrl(DungeonRouteCollection $dungeonRouteCollection): string
    {
        return route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }
}
