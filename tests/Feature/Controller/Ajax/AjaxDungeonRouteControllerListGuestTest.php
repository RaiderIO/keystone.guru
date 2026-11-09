<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * /ajax/routes requested without a session: whatever flags the guest sends, only routes published to the
 * world are listed.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerListGuestTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Auth::guard()->forgetUser();
    }

    /**
     * @param array<string, mixed> $flags
     */
    #[Test]
    #[DataProvider('guestFlagsProvider')]
    public function get_givenGuestWithFlag_returnsOnlyTheWorldPublishedRoute(array $flags): void
    {
        // Arrange
        $author = null;
        $routes = [];

        try {
            $author = User::factory()->create();
            $author->addRole(Role::ROLE_USER);
            $title = sprintf('guest-scope-%s', uniqid());
            foreach ([PublishedState::WORLD, PublishedState::WORLD_WITH_LINK, PublishedState::TEAM, PublishedState::UNPUBLISHED] as $publishedState) {
                $routes[$publishedState] = $this->createRoute($author, [
                    'title'              => $title,
                    'published_state_id' => PublishedState::ALL[$publishedState],
                ]);
            }

            // Act
            $response = $this->get($this->routesQuery($title, $flags));

            // Assert
            $response->assertOk();
            $this->assertSame(
                [$routes[PublishedState::WORLD]->public_key],
                array_column($response->json('data'), 'public_key'),
            );
        } finally {
            foreach ($routes as $route) {
                $route->delete();
            }
            $author?->delete();
        }
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function guestFlagsProvider(): array
    {
        return [
            'no flags'                      => [[]],
            'unknown team public key'       => [['team_public_key' => 'doesnotexist']],
            'team public key and available' => [['team_public_key' => 'doesnotexist', 'available' => 1]],
            'mine'                          => [['mine' => 1]],
        ];
    }

    #[Test]
    public function get_givenGuestAndAWorldRouteWithPersonalTags_doesNotReturnTheAuthorsPersonalTags(): void
    {
        // Arrange
        $author = null;
        $route  = null;

        try {
            $author = User::factory()->create();
            $author->addRole(Role::ROLE_USER);
            $route = $this->createRoute($author, ['title' => sprintf('guest-tags-%s', uniqid())]);
            Tag::query()->create([
                'context_id'      => $author->id,
                'context_class'   => User::class,
                'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
                'model_id'        => $route->id,
                'model_class'     => DungeonRoute::class,
                'name'            => 'Private label',
                'color'           => '#ff0000',
            ]);

            // Act
            $response = $this->get($this->routesQuery($route->title, []));

            // Assert
            $response->assertOk();
            $this->assertSame([$route->public_key], array_column($response->json('data'), 'public_key'));
            $this->assertEmpty($response->json('data.0.tagspersonal') ?? []);
        } finally {
            Tag::query()->where('context_id', $author?->id)->where('context_class', User::class)->delete();
            $route?->delete();
            $author?->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createRoute(User $author, array $attributes): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, dungeonActive: true);

        return DungeonRoute::factory()->create(array_merge([
            'author_id'          => $author->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'team_id'            => null,
            // A sandbox route never shows up in a route table
            'expires_at' => null,
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $flags
     */
    private function routesQuery(string $title, array $flags): string
    {
        return sprintf('/ajax/routes?%s', http_build_query(array_merge([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 'title',
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => $title, 'regex' => 'false'],
                ],
            ],
            'order'  => [['column' => 0, 'dir' => 'asc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ], $flags)));
    }
}
