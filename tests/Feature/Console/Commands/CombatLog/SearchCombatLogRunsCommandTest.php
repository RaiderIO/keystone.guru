<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\Season;
use App\Service\RaiderIO\Dtos\SearchAdvancedRun;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Mockery;
use Mockery\Expectation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
final class SearchCombatLogRunsCommandTest extends PublicTestCase
{
    private Season $season;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->season = Season::query()->firstOrFail();

        $seasonServiceMock = Mockery::mock(SeasonServiceInterface::class);
        $seasonServiceMock->shouldReceive('getCurrentSeason')->andReturn($this->season);

        /** @var SeasonServiceInterface $seasonService */
        $seasonService = $seasonServiceMock;
        app()->instance(SeasonServiceInterface::class, $seasonService);
    }

    #[Test]
    public function handle_givenRunsReturned_exitsSuccessfullyAndPrintsRunIds(): void
    {
        // Arrange
        $run1 = $this->makeRun(1101, 999901);
        $run2 = $this->makeRun(1102, 999902);

        $raiderIOServiceMock = Mockery::mock(RaiderIOApiServiceInterface::class);
        /** @var Expectation $expectation */
        $expectation = $raiderIOServiceMock->shouldReceive('searchAdvancedRuns');
        $expectation->once()->andReturn(new SearchAdvancedRunsResponse([$run1, $run2], 2));

        /** @var RaiderIOApiServiceInterface $raiderIOService */
        $raiderIOService = $raiderIOServiceMock;
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

        // Act + Assert
        $this->artisan('combatlog:searchruns')
            ->assertSuccessful()
            ->expectsOutputToContain((string)$run1->id)
            ->expectsOutputToContain((string)$run2->id);
    }

    #[Test]
    public function handle_givenClassFilter_passesExactlyThatClassesSpecIdsInFilter(): void
    {
        // Arrange
        $rogueClass      = CharacterClass::query()->where('key', CharacterClass::CHARACTER_CLASS_ROGUE)->firstOrFail();
        $expectedSpecIds = CharacterClassSpecialization::query()
            ->where('character_class_id', $rogueClass->id)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertNotEmpty($expectedSpecIds, 'Seeded DB must have rogue specializations for this test to be meaningful.');

        $raiderIOServiceMock = Mockery::mock(RaiderIOApiServiceInterface::class);
        /** @var Expectation $expectation */
        $expectation = $raiderIOServiceMock->shouldReceive('searchAdvancedRuns');
        $expectation->once()
            ->with(Mockery::on(function (SearchAdvancedRunsFilter $filter) use ($expectedSpecIds): bool {
                $actualSpecIds = $filter->specs->pluck('id')->sort()->values()->all();

                return $actualSpecIds === $expectedSpecIds;
            }))
            ->andReturn(new SearchAdvancedRunsResponse([], 0));

        /** @var RaiderIOApiServiceInterface $raiderIOService */
        $raiderIOService = $raiderIOServiceMock;
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

        // Act + Assert
        $this->artisan('combatlog:searchruns', ['--class' => [CharacterClass::CHARACTER_CLASS_ROGUE]])
            ->assertSuccessful();
    }

    #[Test]
    public function handle_givenUnknownDungeonOption_returnsFailure(): void
    {
        // Arrange
        $raiderIOServiceMock = Mockery::mock(RaiderIOApiServiceInterface::class);
        /** @var Expectation $expectation */
        $expectation = $raiderIOServiceMock->shouldReceive('searchAdvancedRuns');
        $expectation->never();

        /** @var RaiderIOApiServiceInterface $raiderIOService */
        $raiderIOService = $raiderIOServiceMock;
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

        // Act + Assert
        $this->artisan('combatlog:searchruns', ['--dungeon' => 'not-a-real-dungeon-key'])
            ->assertFailed();
    }

    #[Test]
    public function handle_givenNoCurrentSeason_exitsSuccessfullyWithoutSearching(): void
    {
        // Arrange
        $seasonServiceMock = Mockery::mock(SeasonServiceInterface::class);
        $seasonServiceMock->shouldReceive('getCurrentSeason')->andReturn(null);

        /** @var SeasonServiceInterface $seasonService */
        $seasonService = $seasonServiceMock;
        app()->instance(SeasonServiceInterface::class, $seasonService);

        $raiderIOServiceMock = Mockery::mock(RaiderIOApiServiceInterface::class);
        /** @var Expectation $expectation */
        $expectation = $raiderIOServiceMock->shouldReceive('searchAdvancedRuns');
        $expectation->never();

        /** @var RaiderIOApiServiceInterface $raiderIOService */
        $raiderIOService = $raiderIOServiceMock;
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

        // Act + Assert
        $this->artisan('combatlog:searchruns')->assertSuccessful();
    }

    private function makeRun(int $id, int $challengeModeId): SearchAdvancedRun
    {
        return new SearchAdvancedRun(
            id:              $id,
            challengeModeId: $challengeModeId,
            dungeonZoneId:   0,
            memberSpecIds:   [66, 70],
            mythicLevel:     10,
            affixes:         [],
        );
    }
}
