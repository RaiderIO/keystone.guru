<?php

namespace Tests\Feature\Console\Commands\MapContext;

use App\Console\Commands\MapContext\MakeMapContextDungeon;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('MapContext')]
final class ResolvesMapContextScopeTest extends PublicTestCase
{
    #[Test]
    public function resolveDungeonIdsForScope_givenPriority_returnsNonEmptyStrictSubsetOfAllDungeons(): void
    {
        // Arrange
        $seasonService = app(SeasonServiceInterface::class);
        $allDungeonIds = Dungeon::query()->pluck('id');

        // Act
        $result = $this->resolveScope('priority', $seasonService);

        // Assert
        $this->assertNotNull($result);
        $this->assertNotEmpty($result);
        $this->assertTrue($result->diff($allDungeonIds)->isEmpty(), 'Every priority dungeon must be part of all dungeons');
        $this->assertLessThan($allDungeonIds->count(), $result->count(), 'priority must be a strict subset of all dungeons');
    }

    #[Test]
    public function resolveDungeonIdsForScope_givenPriorityAndNextSeasonKnown_includesNextSeasonDungeons(): void
    {
        // Arrange - stub the season service so this doesn't depend on which season line is
        // currently live in the seeded data. TWW S1/S2 have disjoint dungeon rosters, which
        // is used here to prove both get merged into "priority" (review on #3520: the next
        // season already receives traffic before it goes fully active, so it must be
        // included alongside the current one).
        $currentSeason = Season::find(Season::SEASON_TWW_S1);
        $nextSeason    = Season::find(Season::SEASON_TWW_S2);

        $seasonServiceMock = Mockery::mock(SeasonServiceInterface::class);
        $seasonServiceMock->shouldReceive('getCurrentSeason')->andReturn($currentSeason);
        $seasonServiceMock->shouldReceive('getNextSeasonOfExpansion')->andReturn($nextSeason);

        /** @var SeasonServiceInterface $seasonService */
        $seasonService = $seasonServiceMock;

        // Act
        $result = $this->resolveScope('priority', $seasonService);

        // Assert
        $this->assertNotNull($result);
        $this->assertEqualsCanonicalizing(
            $currentSeason->dungeons()->pluck('dungeons.id')
                ->merge($nextSeason->dungeons()->pluck('dungeons.id'))
                ->unique()
                ->values()
                ->all(),
            $result->values()->all(),
        );
    }

    #[Test]
    public function resolveDungeonIdsForScope_givenRest_returnsExactComplementOfPriority(): void
    {
        // Arrange
        $seasonService = app(SeasonServiceInterface::class);
        $allDungeonIds = Dungeon::query()->pluck('id');

        // Act
        $priorityDungeonIds = $this->resolveScope('priority', $seasonService);
        $restDungeonIds     = $this->resolveScope('rest', $seasonService);

        // Assert
        $this->assertNotNull($priorityDungeonIds);
        $this->assertNotNull($restDungeonIds);
        $this->assertEqualsCanonicalizing(
            $allDungeonIds->diff($priorityDungeonIds)->values()->all(),
            $restDungeonIds->values()->all(),
        );
        $this->assertTrue($restDungeonIds->intersect($priorityDungeonIds)->isEmpty(), 'rest and priority must not overlap');
        $this->assertEqualsCanonicalizing(
            $allDungeonIds->values()->all(),
            $restDungeonIds->merge($priorityDungeonIds)->unique()->values()->all(),
            'The union of rest and priority must equal all dungeons',
        );
    }

    #[Test]
    public function resolveDungeonIdsForScope_givenAll_returnsAllDungeons(): void
    {
        // Arrange
        $seasonService = app(SeasonServiceInterface::class);
        $allDungeonIds = Dungeon::query()->pluck('id');

        // Act
        $result = $this->resolveScope('all', $seasonService);

        // Assert
        $this->assertNotNull($result);
        $this->assertEqualsCanonicalizing($allDungeonIds->values()->all(), $result->values()->all());
    }

    #[Test]
    public function resolveDungeonIdsForScope_givenInvalidScope_returnsNull(): void
    {
        // Arrange
        $seasonService = app(SeasonServiceInterface::class);

        // Act
        $result = $this->resolveScope('not-a-real-scope', $seasonService);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function handle_givenInvalidScopeOption_failsWithNonZeroExitCode(): void
    {
        // Act + Assert
        $this->artisan('make:mapcontextdungeon', [
            '--output' => sys_get_temp_dir(),
            '--scope'  => 'not-a-real-scope',
        ])->assertFailed();
    }

    /**
     * Invokes the protected trait method shared by the map context generator commands, using the
     * real command class rather than a test double so the behavior under test matches production.
     *
     * @return Collection<int, int>|null
     */
    private function resolveScope(string $scope, SeasonServiceInterface $seasonService): ?Collection
    {
        $command = new MakeMapContextDungeon();

        // The trait writes to the command's output when the scope is invalid, so it needs one even
        // though this command is never actually run through the console kernel here.
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput()));

        $method = new ReflectionMethod($command, 'resolveDungeonIdsForScope');

        /** @var Collection<int, int>|null $result */
        $result = $method->invoke($command, $scope, $seasonService);

        return $result;
    }
}
