<?php

namespace Tests\Unit\App\Http\Controllers\Traits;

use App\Http\Controllers\Traits\ScopesToDungeonRoute;
use App\Models\Arrow;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Models\Interfaces\BelongsToDungeonRouteInterface;
use App\Models\KillZone\KillZone;
use App\Models\LiveSession;
use App\Models\Path;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Teapot\StatusCode\Http;
use Tests\TestCases\PublicTestCase;
use TypeError;

#[Group('Controller')]
#[Group('ScopesToDungeonRoute')]
final class ScopesToDungeonRouteTest extends PublicTestCase
{
    private const int DUNGEON_ROUTE_ID       = 1001;
    private const int OTHER_DUNGEON_ROUTE_ID = 1002;

    /**
     * @param class-string<Model> $modelClass
     */
    #[Test]
    #[DataProvider('dungeonRouteChildModelProvider')]
    public function abortUnlessBelongsToDungeonRoute_givenModelOfSameRoute_doesNotAbort(string $modelClass): void
    {
        // Arrange
        $model = $this->makeModel($modelClass, self::DUNGEON_ROUTE_ID);

        // Act
        $this->scope($this->makeDungeonRoute(), $model);

        // Assert
        $this->addToAssertionCount(1);
    }

    /**
     * @param class-string<Model> $modelClass
     */
    #[Test]
    #[DataProvider('dungeonRouteChildModelProvider')]
    public function model_givenDungeonRouteChildModel_implementsBelongsToDungeonRouteInterface(string $modelClass): void
    {
        // Arrange
        $model = new $modelClass();

        // Act
        $implementedInterfaces = class_implements($model);

        // Assert
        $this->assertContains(BelongsToDungeonRouteInterface::class, $implementedInterfaces);
    }

    /**
     * @param class-string<Model> $modelClass
     */
    #[Test]
    #[DataProvider('dungeonRouteChildModelProvider')]
    public function abortUnlessBelongsToDungeonRoute_givenModelOfOtherRoute_throwsNotFound(string $modelClass): void
    {
        // Arrange
        $model = $this->makeModel($modelClass, self::OTHER_DUNGEON_ROUTE_ID);

        // Act
        try {
            $this->scope($this->makeDungeonRoute(), $model);
            $this->fail('Expected the scope check to abort');
        } catch (HttpException $httpException) {
            // Assert
            $this->assertSame(Http::NOT_FOUND, $httpException->getStatusCode());
        }
    }

    /**
     * @return array<string, array{class-string<Model>}>
     */
    public static function dungeonRouteChildModelProvider(): array
    {
        return [
            'Arrow'       => [Arrow::class],
            'Brushline'   => [Brushline::class],
            'Path'        => [Path::class],
            'KillZone'    => [KillZone::class],
            'LiveSession' => [LiveSession::class],
        ];
    }

    #[Test]
    public function abortUnlessBelongsToDungeonRoute_givenNull_doesNotAbort(): void
    {
        // Arrange
        $dungeonRoute = $this->makeDungeonRoute();

        // Act
        $this->scope($dungeonRoute, null);

        // Assert
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function abortUnlessBelongsToDungeonRoute_givenModelNotImplementingInterface_throwsTypeError(): void
    {
        // Arrange
        $model = $this->makeModel(DungeonRouteFavorite::class, self::DUNGEON_ROUTE_ID);

        // Assert
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(BelongsToDungeonRouteInterface::class);

        // Act
        $this->scope($this->makeDungeonRoute(), $model);
    }

    private function makeDungeonRoute(): DungeonRoute
    {
        $dungeonRoute     = new DungeonRoute();
        $dungeonRoute->id = self::DUNGEON_ROUTE_ID;

        return $dungeonRoute;
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function makeModel(string $modelClass, int $dungeonRouteId): Model
    {
        $model = new $modelClass();
        $model->setAttribute('dungeon_route_id', $dungeonRouteId);

        return $model;
    }

    private function scope(DungeonRoute $dungeonRoute, mixed $model): void
    {
        $scoper = new class {
            use ScopesToDungeonRoute;

            public function check(DungeonRoute $dungeonRoute, mixed $model): void
            {
                $this->abortUnlessBelongsToDungeonRoute($dungeonRoute, $model);
            }
        };

        $scoper->check($dungeonRoute, $model);
    }
}
