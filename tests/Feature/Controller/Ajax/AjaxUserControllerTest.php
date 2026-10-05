<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('User')]
final class AjaxUserControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function get_givenColumnEntryWithNameOnly_returnsOk(): void
    {
        // Arrange - a columns[] entry that carries a name but neither 'searchable' nor 'orderable',
        // as seen in PHP-LARAVEL-S9 (#4438): a partial datatables columns payload from the client
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data' => 0,
                    'name' => 'name',
                ],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/admin/user?%s', $query));

        // Assert
        $response->assertOk();
        $this->assertUserRowsAreDecorated($response);
    }

    #[Test]
    public function get_givenOrderWithoutDirection_returnsOk(): void
    {
        // Arrange - an order[] entry that names a column but omits 'dir'
        $query = http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 0,
                    'name'       => 'name',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
            ],
            'order'  => [['column' => 0]],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);

        // Act
        $response = $this->get(sprintf('/ajax/admin/user?%s', $query));

        // Assert
        $response->assertOk();
        $this->assertUserRowsAreDecorated($response);
    }

    #[Test]
    public function store_givenAMapFacadeStyle_updatesIt(): void
    {
        $user = null;

        try {
            // Arrange
            $user = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);
            $user->addRole(Role::ROLE_USER);
            $this->actingAs($user);

            // Act
            $response = $this->putJson(sprintf('/ajax/user/%s', $user->public_key), [
                'map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS,
            ]);

            // Assert
            $response->assertOk();
            $this->assertSame(User::MAP_FACADE_STYLE_SPLIT_FLOORS, $user->refresh()->map_facade_style);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function store_givenAnEmptyMapFacadeStyle_returnsValidationErrorAndKeepsIt(): void
    {
        $user = null;

        try {
            // Arrange
            $user = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);
            $user->addRole(Role::ROLE_USER);
            $this->actingAs($user);

            // Act
            $response = $this->putJson(sprintf('/ajax/user/%s', $user->public_key), [
                'map_facade_style' => '',
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['map_facade_style']);
            $this->assertSame(User::MAP_FACADE_STYLE_FACADE, $user->refresh()->map_facade_style);
        } finally {
            $user?->delete();
        }
    }

    /**
     * @param TestResponse<\Symfony\Component\HttpFoundation\Response> $response
     */
    private function assertUserRowsAreDecorated(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => ['id', 'name', 'roles_string', 'routes', 'ip_addresses_string'],
            ],
        ]);

        $this->assertNotEmpty($response->json('data'));
    }
}
