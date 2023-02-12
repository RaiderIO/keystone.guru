<?php

namespace App\Service\Season;

use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\PublicTestCase;
use Tests\Unit\Fixtures\ServiceFixtures;

class SeasonServiceTest extends PublicTestCase
{
    /** @var Collection|array{start: Carbon, affix_group_count: int} */
    private Collection $seasonAttributes;

    /** @var Collection|Season[] */
    private Collection $seasons;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->seasonAttributes = collect([
            [
                'start'             => Carbon::createFromDate(2018, 9, 4),
                'affix_group_count' => 12,
            ], [
                'start'             => Carbon::createFromDate(2019, 01, 23),
                'affix_group_count' => 10,
            ], [
                'start'             => Carbon::createFromDate(2019, 07, 10),
                'affix_group_count' => 8,
            ], [
                'start'             => Carbon::createFromDate(2020, 01, 21),
                'affix_group_count' => 6,
            ],
        ]);

        parent::__construct($name, $data, $dataName);
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->seasons = collect();
        foreach ($this->seasonAttributes as $attributes) {
            $this->seasons->push(new Season($attributes));
        }
    }


    /**
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

        dump($date->toDateString(), $iterations);

        // Assert
        self::assertEquals(1, 1);
    }

    /**
     * @return array
     */
    public function getIterationsAt_givenVariousDates_shouldReturnCorrectIterations_DataProvider(): array
    {
        $result = [];
        /** @var array{start: Carbon, affix_group_count: int} $seasonAttributes */
        $seasonAttributes = $this->seasonAttributes->get(0);
        $date             = $seasonAttributes['start'];

        for ($i = 0; $i < 100; $i++) {
            $result[] = [
                $date,
                0,
            ];

            $date = $date->clone()->addWeek();
        }

//        /** @var array{start: Carbon, affix_group_count: int} $seasonAttributes */
//        $seasonAttributes   = $this->seasonAttributes->get(0);
//        $date               = $seasonAttributes['start'];
//        $seasonIndex        = 0;
//        $expectedIterations = 0;

//        do {
//            $result[] = [$date, $expectedIterations];
//
//            $date = $date->clone()->addWeeks($seasonAttributes['affix_group_count']);
//            $expectedIterations++;
//
//            // Switch to next season as necessary
//            /** @var array{start: Carbon, affix_group_count: int} $nextSeason */
//            $nextSeason    = $this->seasonAttributes->get($seasonIndex + 1);
//            $hasNextSeason = $nextSeason !== null;
//
//            if ($hasNextSeason) {
//                // If this season has started yet
//                if ($nextSeason['start']->isBefore($date)) {
//                    $seasonAttributes = $nextSeason;
//                    $seasonIndex++;
//                }
//            }
//        } while ($hasNextSeason);

        return $result;
    }
}
