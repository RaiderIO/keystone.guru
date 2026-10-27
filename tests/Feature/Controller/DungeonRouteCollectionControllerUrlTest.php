<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\Laratrust\Role;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class DungeonRouteCollectionControllerUrlTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('routeNameProvider')]
    public function route_givenACollectionRouteName_returnsTheCollectionUrl(string $routeName, string $expectedPathFormat): void
    {
        // Arrange
        $dungeonRouteCollection = new DungeonRouteCollection(['public_key' => 'AbCdEfG']);

        // Act
        $url = route($routeName, ['dungeonRouteCollection' => $dungeonRouteCollection], false);

        // Assert
        $this->assertSame(sprintf($expectedPathFormat, 'AbCdEfG'), $url);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function routeNameProvider(): array
    {
        return [
            'view'      => ['collection.view', '/collection/%s'],
            'edit'      => ['collection.edit', '/collection/%s/edit'],
            'update'    => ['collection.update', '/collection/%s'],
            'delete'    => ['collection.delete', '/collection/%s'],
            'duplicate' => ['collection.duplicate', '/collection/%s/duplicate'],
        ];
    }

    #[Test]
    public function route_givenTheCreateRouteNames_returnsTheNewCollectionUrl(): void
    {
        // Arrange - nothing to arrange; the create routes take no parameters

        // Act
        $newUrl     = route('collection.new', [], false);
        $saveNewUrl = route('collection.savenew', [], false);
        $indexUrl   = route('collections.index', [], false);

        // Assert
        $this->assertSame('/collection/new', $newUrl);
        $this->assertSame('/collection/new', $saveNewUrl);
        $this->assertSame('/collections', $indexUrl);
    }

    #[Test]
    public function create_givenTheNewCollectionUrl_rendersTheCreatePageInsteadOfAViewLookup(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->get('/collection/new');

            // Assert
            $response->assertOk();
            $response->assertViewIs('collection.new');
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function edit_givenTheEditUrlOfAnOwnCollection_rendersTheEditPage(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);
        $dungeonRouteCollection = null;

        try {
            $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);

            // Act
            $response = $this->actingAs($creator)->get(sprintf('/collection/%s/edit', $dungeonRouteCollection->public_key));

            // Assert
            $response->assertOk();
            $response->assertViewIs('collection.edit');
        } finally {
            $dungeonRouteCollection?->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function edit_givenAGuest_redirectsToTheLoginPage(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for(null)->activate(CreatorProfiles::class);
        $dungeonRouteCollection = null;

        try {
            $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);

            // Act
            $response = $this->get(sprintf('/collection/%s/edit', $dungeonRouteCollection->public_key));

            // Assert
            $response->assertRedirect(route('login'));
        } finally {
            $dungeonRouteCollection?->delete();
            Feature::for(null)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function legacyEditUrl_givenAnOwnCollection_redirectsPermanentlyToTheEditUrl(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);
        $dungeonRouteCollection = null;

        try {
            $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);

            // Act
            $response = $this->actingAs($creator)->get(sprintf('/collections/%s', $dungeonRouteCollection->public_key));

            // Assert
            $response->assertStatus(301);
            $response->assertRedirect(sprintf('/collection/%s/edit', $dungeonRouteCollection->public_key));
        } finally {
            $dungeonRouteCollection?->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    private function createCreator(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }
}
