<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MDTImport;
use App\Models\User;
use App\Service\MDT\MDTImportStringServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDT')]
final class MDTImportStringServiceUserTest extends MDTImportStringServiceTestBase
{
    #[Test]
    public function getDungeonRoute_givenUserPassedWhileOtherUserAuthenticated_authorsRouteAsPassedUser(): void
    {
        // Arrange
        $authenticatedUser = User::factory()->create();
        $passedUser        = User::factory()->create();
        $dungeonRoute      = null;
        $source            = $this->getMDTCompatibleNonFacadeDungeonRoute();
        $string            = $this->exportDungeonRouteToString($source);

        try {
            $this->be($authenticatedUser);

            // Act
            $dungeonRoute = $this->import($string, user: $passedUser);

            // Assert
            $this->assertSame($passedUser->id, $dungeonRoute->author_id);
        } finally {
            $dungeonRoute?->delete();
            $source->delete();
            MDTImport::query()->where('import_string', $string)->delete();
            $authenticatedUser->delete();
            $passedUser->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenNoUserPassedWhileUserAuthenticated_authorsRouteAsNobody(): void
    {
        // Arrange
        $authenticatedUser = User::factory()->create();
        $dungeonRoute      = null;
        $source            = $this->getMDTCompatibleNonFacadeDungeonRoute();
        $string            = $this->exportDungeonRouteToString($source);

        try {
            $this->be($authenticatedUser);

            // Act
            $dungeonRoute = $this->import($string, user: null);

            // Assert
            $this->assertSame(-1, $dungeonRoute->author_id);
        } finally {
            $dungeonRoute?->delete();
            $source->delete();
            MDTImport::query()->where('import_string', $string)->delete();
            $authenticatedUser->delete();
        }
    }

    private function import(string $string, ?User $user): DungeonRoute
    {
        return app()->make(MDTImportStringServiceInterface::class)
            ->setEncodedString($string)
            ->getDungeonRoute(
                warnings: new Collection(),
                errors: new Collection(),
                sandbox: false,
                save: false,
                user: $user,
            );
    }
}
