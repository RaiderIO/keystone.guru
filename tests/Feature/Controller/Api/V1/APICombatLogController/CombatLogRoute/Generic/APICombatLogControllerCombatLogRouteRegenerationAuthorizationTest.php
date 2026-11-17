<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\Generic;

use App\Models\DungeonKey;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

/**
 * Posting a combat log with settings.publicKey regenerates the existing combat log route with that key. Only the
 * route's author or an admin may do so; posting without a public key always creates a new route.
 */
#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('CombatLogRouteGeneric')]
#[Group('CombatLogRouteRegenerationAuthorization')]
class APICombatLogControllerCombatLogRouteRegenerationAuthorizationTest extends APICombatLogControllerCombatLogRouteTestBase
{
    private const FIXTURE = 'BFA/midnight_s2_kings_rest';

    protected function getDungeonKey(): string
    {
        return DungeonKey::KINGS_REST->value;
    }

    #[Test]
    public function store_givenPublicKeyOfOwnCombatLogRoute_regeneratesThatRoute(): void
    {
        $author = null;

        try {
            // Arrange
            $author = User::factory()->create();
            $this->actingAs($author);
            $original    = $this->storeOriginalRoute();
            $killZoneIds = $original->killZones()->pluck('id')->sort()->values()->all();
            $this->assertNotEmpty($killZoneIds);

            // Act
            $response = $this->post(route('api.v1.combatlog.route.store'), $this->getRegenerationPostBody($original));

            // Assert
            $response->assertOk();
            $this->assertSame($original->public_key, $response->json('data.publicKey'));
            $this->assertNotSame($killZoneIds, $original->killZones()->pluck('id')->sort()->values()->all());
            $this->assertSame(0, DungeonRoute::query()->where('author_id', $author->id)->where('id', '!=', $original->id)->count());
        } finally {
            $author?->delete();
        }
    }

    #[Test]
    public function store_givenPublicKeyOfAnotherUsersCombatLogRoute_returnsForbiddenAndLeavesRouteUntouched(): void
    {
        $author = null;
        $other  = null;

        try {
            // Arrange
            $author = User::factory()->create();
            $other  = User::factory()->create();
            $this->actingAs($author);
            $original    = $this->storeOriginalRoute();
            $killZoneIds = $original->killZones()->pluck('id')->sort()->values()->all();
            $this->assertNotEmpty($killZoneIds);
            $this->actingAs($other);

            // Act
            $response = $this->post(route('api.v1.combatlog.route.store'), $this->getRegenerationPostBody($original));

            // Assert
            $response->assertForbidden();
            $this->assertSame($killZoneIds, $original->killZones()->pluck('id')->sort()->values()->all());
            $this->assertNull($original->upgradeDraft()->first());
            $this->assertSame(0, DungeonRoute::query()->where('author_id', $other->id)->count());
        } finally {
            $other?->delete();
            $author?->delete();
        }
    }

    #[Test]
    public function store_givenPublicKeyOfAnotherUsersCombatLogRouteAsAdmin_regeneratesThatRoute(): void
    {
        $author = null;

        try {
            // Arrange
            $admin = User::findOrFail(1);
            $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
            $author = User::factory()->create();
            $this->actingAs($author);
            $original = $this->storeOriginalRoute();
            $this->actingAs($admin);

            // Act
            $response = $this->post(route('api.v1.combatlog.route.store'), $this->getRegenerationPostBody($original));

            // Assert
            $response->assertOk();
            $this->assertSame($original->public_key, $response->json('data.publicKey'));
            $this->assertSame($author->id, $original->fresh()->author_id);
        } finally {
            $author?->delete();
        }
    }

    #[Test]
    public function store_givenNoPublicKeyAsNonAdmin_createsNewRouteForCaller(): void
    {
        $user = null;

        try {
            // Arrange
            $user = User::factory()->create();
            $this->actingAs($user);
            $postBody = $this->getJsonData(self::FIXTURE, self::FIXTURES_ROOT_DIR);
            unset($postBody['settings']['publicKey']);

            // Act
            $responseArr = $this->storeCombatLogRoute($postBody);

            // Assert
            $created = DungeonRoute::query()->where('public_key', $responseArr['data']['publicKey'])->firstOrFail();
            $this->assertSame($user->id, $created->author_id);
        } finally {
            $user?->delete();
        }
    }

    private function storeOriginalRoute(): DungeonRoute
    {
        $postBody = $this->getJsonData(self::FIXTURE, self::FIXTURES_ROOT_DIR);
        unset($postBody['settings']['publicKey']);

        $responseArr = $this->storeCombatLogRoute($postBody);

        return DungeonRoute::query()->where('public_key', $responseArr['data']['publicKey'])->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function getRegenerationPostBody(DungeonRoute $dungeonRoute): array
    {
        $postBody                          = $this->getJsonData(self::FIXTURE, self::FIXTURES_ROOT_DIR);
        $postBody['settings']['publicKey'] = $dungeonRoute->public_key;

        return $postBody;
    }
}
