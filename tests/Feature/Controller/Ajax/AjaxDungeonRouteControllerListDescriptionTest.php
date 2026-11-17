<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * The route descriptions /ajax/routes returns are sanitised, whatever is stored.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerListDescriptionTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function get_givenRouteWithUnsanitisedDescription_returnsSanitisedDescription(): void
    {
        // Arrange
        $user  = null;
        $route = null;

        try {
            $user = User::factory()->create();
            $user->addRole(Role::ROLE_USER);
            [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, dungeonActive: true);
            $route                      = DungeonRoute::factory()->create([
                'author_id'          => $user->id,
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $mappingVersion->id,
                'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
                'expires_at'         => null,
                'description'        => 'Pull left<img src=x onerror="alert(1)"><b onclick="alert(1)">now</b>',
            ]);
            $this->actingAs($user);

            // Act
            $response = $this->get(sprintf('/ajax/routes?%s', http_build_query([
                'draw'    => 1,
                'start'   => 0,
                'length'  => 25,
                'mine'    => 1,
                'columns' => [[
                    'data'       => 'title',
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ]],
                'order'  => [['column' => 0, 'dir' => 'asc']],
                'search' => ['value' => '', 'regex' => 'false'],
            ])));

            // Assert
            $response->assertOk();
            $this->assertSame([$route->public_key], array_column($response->json('data'), 'public_key'));
            $this->assertSame('Pull left<b>now</b>', $response->json('data.0.description'));
        } finally {
            $route?->delete();
            $user?->delete();
        }
    }
}
