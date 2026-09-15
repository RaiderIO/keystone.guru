<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\CharacterRace;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\DungeonRoute\DungeonRouteSaveService;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

#[Group('DungeonRouteSaveService')]
#[Group('DungeonRouteSaveServiceChildRelations')]
final class DungeonRouteSaveServiceChildRelationsTest extends DungeonRouteSaveServiceTestCase
{
    #[Test]
    public function save_givenANewRouteWithRaces_issuesNoDeleteOnTheChildTables(): void
    {
        // Arrange - a delete on a route id that has no rows yet only takes gap locks, which
        // deadlock against concurrent route creations
        $user    = $this->createUser();
        $raceIds = $this->getRaceIds(2);
        $route   = new DungeonRoute();

        $deletes = [];
        DB::listen(static function (QueryExecuted $query) use (&$deletes): void {
            if (str_starts_with(strtolower($query->sql), 'delete from `dungeon_route_')) {
                $deletes[] = $query->sql;
            }
        });

        try {
            // Act
            Auth::login($user);
            $result = $this->buildNewRouteService()->save($route, $this->newRouteRequest(['race' => $raceIds]));

            // Assert
            $this->assertTrue($result);
            $this->assertSame([], $deletes);
            $this->assertEqualsCanonicalizing($raceIds, $this->storedRaceIds($route));
        } finally {
            $this->cleanUp($route, $user);
        }
    }

    #[Test]
    public function save_givenANewRouteWithOnlyUnsetRaceSelects_storesNoRaceRows(): void
    {
        // Arrange - every empty race select in the group composition submits the "0" sentinel
        $user  = $this->createUser();
        $route = new DungeonRoute();

        try {
            // Act
            Auth::login($user);
            $result = $this->buildNewRouteService()->save($route, $this->newRouteRequest(['race' => ['0', '0', '0', '0', '0']]));

            // Assert
            $this->assertTrue($result);
            $this->assertSame([], $this->storedRaceIds($route));
        } finally {
            $this->cleanUp($route, $user);
        }
    }

    #[Test]
    public function save_givenRacesMixedWithUnsetAndUnknownIds_storesOnlyTheKnownRacesPerSlot(): void
    {
        // Arrange - two slots sharing a race are two rows, the group composition reads one per slot
        $user    = $this->createUser();
        $raceIds = $this->getRaceIds(2);
        $unknown = (int)CharacterRace::query()->max('id') + 1000;
        $route   = new DungeonRoute();

        try {
            // Act
            Auth::login($user);
            $result = $this->buildNewRouteService()->save($route, $this->newRouteRequest([
                'race' => ['0', (string)$raceIds[0], (string)$unknown, (string)$raceIds[1], (string)$raceIds[0]],
            ]));

            // Assert
            $this->assertTrue($result);
            $this->assertEqualsCanonicalizing([$raceIds[0], $raceIds[1], $raceIds[0]], $this->storedRaceIds($route));
        } finally {
            $this->cleanUp($route, $user);
        }
    }

    #[Test]
    public function save_givenAnExistingRouteWithRaces_replacesItsPreviousRaces(): void
    {
        // Arrange
        $user                    = $this->createUser();
        [$oldRaceId, $newRaceId] = $this->getRaceIds(2);
        $dungeon                 = $this->getRetailDungeon();
        $route                   = DungeonRoute::factory()->create([
            'author_id'          => $user->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);
        DungeonRoutePlayerRace::insert(['dungeon_route_id' => $route->id, 'character_race_id' => $oldRaceId]);

        try {
            // Act
            Auth::login($user);
            $result = $this->buildService(seasonService: $this->noSeasonService())
                ->save($route, ['race' => [(string)$newRaceId]]);

            // Assert
            $this->assertTrue($result);
            $this->assertSame([$newRaceId], $this->storedRaceIds($route));
        } finally {
            $this->cleanUp($route, $user);
        }
    }

    private function buildNewRouteService(): DungeonRouteSaveService
    {
        return $this->buildService(
            seasonService: $this->noSeasonService(),
            thumbnailService: $this->thumbnailServiceAllowingRefresh(),
        );
    }

    /**
     * @param  array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function newRouteRequest(array $extra): array
    {
        return [
            'dungeon_id'          => $this->getRetailDungeon()->id,
            'faction_id'          => 1,
            'dungeon_route_title' => 'Child relations test route',
        ] + $extra;
    }

    /**
     * @return array<int, int>
     */
    private function getRaceIds(int $count): array
    {
        return CharacterRace::query()->orderBy('id')->limit($count)->pluck('id')->map(static fn($id): int => (int)$id)->all();
    }

    /**
     * @return array<int, int>
     */
    private function storedRaceIds(DungeonRoute $route): array
    {
        return DungeonRoutePlayerRace::query()
            ->where('dungeon_route_id', $route->id)
            ->pluck('character_race_id')
            ->map(static fn($id): int => (int)$id)
            ->all();
    }

    /**
     * @return MockObject&SeasonServiceInterface
     */
    private function noSeasonService(): MockObject
    {
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn(null);

        return $seasonService;
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    private function cleanUp(DungeonRoute $route, User $user): void
    {
        Auth::logout();

        if ($route->exists) {
            DungeonRoutePlayerRace::query()->where('dungeon_route_id', $route->id)->delete();
            $this->cleanupRoute($route);
        }

        $user->delete();
    }
}
