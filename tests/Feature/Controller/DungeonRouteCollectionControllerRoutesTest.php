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
    public function edit_givenASlotOverItsDungeonLimit_marksTheRoutesPastTheLimitAndSaysSo(): void
    {
        // Arrange
        $owner                  = $this->owner();
        $mappingVersion         = $this->retailMappingVersion();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id], [$mappingVersion->dungeon_id]);
        $dungeonRoutes          = array_map(fn(): DungeonRoute => $this->createRoute($mappingVersion), range(0, DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON));
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season), $dungeonRoutes);

        // Act
        $response = $this->actingAs($owner)->get($this->editUrl($dungeonRouteCollection));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        $slotId  = sprintf('dungeon_routes_%d', $mappingVersion->dungeon_id);
        $this->assertSame(
            array_fill(0, DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON, false) + [DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON => true],
            $this->slotItemsOverTheLimit($content, $slotId),
        );
        $this->assertSame(
            __('js.collection_dungeonroutes_over_dungeon_limit', ['max' => DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON]),
            $this->slotNote($content, $slotId, 'over'),
        );
        $this->assertNull($this->slotNote($content, $slotId, 'full'));
    }

    #[Test]
    public function edit_givenASlotAtItsDungeonLimit_marksNoRouteAndSaysItIsFull(): void
    {
        // Arrange
        $owner                  = $this->owner();
        $mappingVersion         = $this->retailMappingVersion();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id], [$mappingVersion->dungeon_id]);
        $dungeonRoutes          = array_map(fn(): DungeonRoute => $this->createRoute($mappingVersion), range(1, DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON));
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season), $dungeonRoutes);

        // Act
        $response = $this->actingAs($owner)->get($this->editUrl($dungeonRouteCollection));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        $slotId  = sprintf('dungeon_routes_%d', $mappingVersion->dungeon_id);
        $this->assertSame(array_fill(0, DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON, false), $this->slotItemsOverTheLimit($content, $slotId));
        $this->assertNull($this->slotNote($content, $slotId, 'over'));
        $this->assertSame(
            __('js.orderedselect_full', ['max' => DungeonRouteCollection::MAX_ROUTES_PER_DUNGEON]),
            $this->slotNote($content, $slotId, 'full'),
        );
    }

    #[Test]
    public function edit_givenTheOwner_labelsEveryFilterOfThePicker(): void
    {
        // Arrange
        $owner                  = $this->owner();
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), []);

        // Act
        $response = $this->actingAs($owner)->get($this->editUrl($dungeonRouteCollection));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        foreach (['dungeon', 'affixes', 'attributes', 'requirements', 'tags'] as $filter) {
            $selectId = sprintf('collection_route_picker_%s', $filter);
            $this->assertMatchesRegularExpression(sprintf('/<label[^>]* for="%s"/', $selectId), $content, $filter);
            $this->assertMatchesRegularExpression(sprintf('/<select[^>]* id="%s"/', $selectId), $content, $filter);
        }
    }

    #[Test]
    public function edit_givenTheOwner_rendersThePickersPagingDisabledUntilAPageIsListed(): void
    {
        // Arrange
        $owner                  = $this->owner();
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), []);

        // Act
        $response = $this->actingAs($owner)->get($this->editUrl($dungeonRouteCollection));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        foreach (['previous', 'next'] as $pageButton) {
            $this->assertMatchesRegularExpression(
                sprintf('/<button id="collection_route_picker_%s"[^>]*\sdisabled aria-disabled="true">/', $pageButton),
                $content,
            );
        }
        $this->assertMatchesRegularExpression(
            '/<button id="collection_route_picker_clear_filters"[^>]*hidden>\s*<i[^>]*><\/i>\s*' . preg_quote(__('view_common.dungeonroute.picker.clear_filters'), '/') . '/',
            $content,
        );
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

    /**
     * Per route of a slot's list, in list order, whether it is marked as past the dungeon limit.
     *
     * @return array<int, bool>
     */
    private function slotItemsOverTheLimit(string $content, string $slotId): array
    {
        $this->assertSame(1, preg_match(sprintf('/<ol id="%s_list"[^>]*>(.*?)<\/ol>/s', $slotId), $content, $list));
        preg_match_all('/<li class="([^"]*)"/', $list[1], $items);

        return array_map(static fn(string $classes): bool => str_contains($classes, 'ordered_select_item_over'), $items[1]);
    }

    /**
     * The text of a slot's note ('full' or 'over'), or null while the note is hidden.
     */
    private function slotNote(string $content, string $slotId, string $note): ?string
    {
        $this->assertSame(1, preg_match(sprintf('/<span id="%s_%s"([^>]*)>(.*?)<\/span>/s', $slotId, $note), $content, $match));

        return str_contains($match[1], 'hidden') ? null : trim(html_entity_decode($match[2], ENT_QUOTES));
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
