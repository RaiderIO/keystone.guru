<?php

namespace App\Service\Season;

use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\PublicTestCase;
use Tests\Unit\Fixtures\ServiceFixtures;

class SeasonServiceTest extends PublicTestCase
{
    /** @var Collection|Season[] */
    private Collection $seasons;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seasons = collect([
            new Season([
                'start'             => Carbon::createFromDate(2018, 9, 4),
                'affix_group_count' => 12,
            ]),
            new Season([
                'start'             => Carbon::createFromDate(2019, 01, 23),
                'affix_group_count' => 12,
            ]),
            new Season([
                'start'             => Carbon::createFromDate(2019, 07, 10),
                'affix_group_count' => 12,
            ]),
            new Season([
                'start'             => Carbon::createFromDate(2020, 01, 21),
                'affix_group_count' => 12,
            ]),
        ]);
    }


    /**
     * @test
     * @group seasonService
     * @param Carbon $date
     * @param int $expectedIterations
     * @return void
     * @dataProvider getIterationsAt_givenVariousDates_shouldReturnCorrectIterations_DataProvider
     */
    public function getIterationsAt_givenVariousDates_shouldReturnCorrectIterations(Carbon $date, int $expectedIterations): void
    {
        // Arrange
        $seasonService = ServiceFixtures::getSeasonServiceMock($this, ['getIterationsAt'], $this->seasons);

        // Act
        $iterations = $seasonService->getIterationsAt($date);

        // Assert
        self::assertEquals($expectedIterations, $iterations);
    }

    /**
     * @return array
     */
    public function getIterationsAt_givenVariousDates_shouldReturnCorrectIterations_DataProvider(): array
    {
        return [
            [
                Carbon::createFromDate(2018, 9, 4),
                0,
            ],
        ];
    }
}
