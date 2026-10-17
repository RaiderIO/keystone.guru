<?php

namespace Tests\Feature\Controller;

use App\Http\Requests\MDT\ImportStringFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\MDT\MDTExportStringServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\PublicTestCase;

#[Group('MDT')]
#[Group('MDTImportController')]
final class MDTImportControllerTest extends PublicTestCase
{
    use GeneratesDungeonRoutes;

    #[Test]
    public function import_givenImportStringOverSizeLimit_returnsUnprocessableWithFieldError(): void
    {
        // Arrange
        $importString = str_repeat('a', ImportStringFormRequest::IMPORT_STRING_MAX_LENGTH + 1);

        // Act
        $response = $this->postJson(route('dungeonroute.new.mdtimport'), [
            'import_string' => $importString,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['import_string']);
    }

    #[Test]
    public function import_givenImportStringAtSizeLimit_passesValidation(): void
    {
        // Arrange - a string of the maximum length that is not an MDT string: it must get past
        // validation and be rejected by the import itself instead
        $importString = str_repeat('a', ImportStringFormRequest::IMPORT_STRING_MAX_LENGTH);

        // Act
        $response = $this->postJson(route('dungeonroute.new.mdtimport'), [
            'import_string' => $importString,
        ]);

        // Assert
        $response->assertBadRequest();
        $response->assertJson(['message' => __('controller.mdtimport.error.mdt_string_parsing_failed')]);
    }

    #[Test]
    public function import_givenAGuest_importsTheRouteAsASandboxRoute(): void
    {
        // Arrange - a guest asking for a non-sandbox route still gets a sandbox one
        $sourceRoute          = $this->getMDTCompatibleNonFacadeDungeonRoute(['author_id' => 1, 'expires_at' => null]);
        $highestRouteIdBefore = (int)DungeonRoute::query()->max('id');

        try {
            // Act
            $response = $this->post(route('dungeonroute.new.mdtimport'), [
                'import_string'      => $this->exportToString($sourceRoute),
                'mdt_import_sandbox' => 0,
            ]);

            // Assert
            $this->assertSame(1, DungeonRoute::query()->where('id', '>', $highestRouteIdBefore)->count());
            $importedRoute = DungeonRoute::query()->where('id', '>', $highestRouteIdBefore)->firstOrFail();
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $importedRoute->dungeon,
                'dungeonroute' => $importedRoute,
                'title'        => $importedRoute->getTitleSlug(),
            ]));
            $this->assertTrue($importedRoute->isSandbox());
        } finally {
            DungeonRoute::query()->where('id', '>', $highestRouteIdBefore)->get()->each->delete();
            $sourceRoute->delete();
        }
    }

    #[Test]
    public function import_givenAUserAtTheirRouteLimit_showsTheLimitReachedPageAndImportsNothing(): void
    {
        // Arrange
        $sourceRoute = $this->getMDTCompatibleNonFacadeDungeonRoute(['author_id' => 1, 'expires_at' => null]);
        $user        = $this->createUserWithRoutes(1);
        config(['keystoneguru.registered_user_dungeonroute_limit' => 1]);
        $highestRouteIdBefore = (int)DungeonRoute::query()->max('id');

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.new.mdtimport'), [
                'import_string'      => $this->exportToString($sourceRoute),
                'mdt_import_sandbox' => 0,
            ]);

            // Assert
            $response->assertOk();
            $response->assertViewIs('dungeonroute.limitreached');
            $this->assertSame(0, DungeonRoute::query()->where('id', '>', $highestRouteIdBefore)->count());
        } finally {
            DungeonRoute::query()->where('id', '>', $highestRouteIdBefore)->get()->each->delete();
            DungeonRoute::query()->where('author_id', $user->id)->get()->each->delete();
            $sourceRoute->delete();
            $user->delete();
        }
    }

    #[Test]
    public function import_givenAUserUnderTheirRouteLimit_importsTheRouteForThatUser(): void
    {
        // Arrange
        $sourceRoute = $this->getMDTCompatibleNonFacadeDungeonRoute(['author_id' => 1, 'expires_at' => null]);
        $user        = $this->createUserWithRoutes(1);
        config(['keystoneguru.registered_user_dungeonroute_limit' => 2]);
        $highestRouteIdBefore = (int)DungeonRoute::query()->max('id');

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.new.mdtimport'), [
                'import_string'      => $this->exportToString($sourceRoute),
                'mdt_import_sandbox' => 0,
            ]);

            // Assert
            $this->assertSame(1, DungeonRoute::query()->where('id', '>', $highestRouteIdBefore)->count());
            $importedRoute = DungeonRoute::query()->where('id', '>', $highestRouteIdBefore)->firstOrFail();
            $response->assertRedirect(route('dungeonroute.edit', [
                'dungeon'      => $importedRoute->dungeon,
                'dungeonroute' => $importedRoute,
                'title'        => $importedRoute->getTitleSlug(),
            ]));
            $this->assertSame($user->id, $importedRoute->author_id);
            $this->assertFalse($importedRoute->isSandbox());
        } finally {
            DungeonRoute::query()->where('id', '>', $highestRouteIdBefore)->get()->each->delete();
            DungeonRoute::query()->where('author_id', $user->id)->get()->each->delete();
            $sourceRoute->delete();
            $user->delete();
        }
    }

    private function createUserWithRoutes(int $routeCount): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        DungeonRoute::factory()->count($routeCount)->create(['author_id' => $user->id, 'expires_at' => null]);

        return $user;
    }

    private function exportToString(DungeonRoute $dungeonRoute): string
    {
        return app()->make(MDTExportStringServiceInterface::class)
            ->setDungeonRoute($dungeonRoute)
            ->getEncodedString(collect());
    }
}
