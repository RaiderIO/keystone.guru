<?php

namespace Tests\Unit\App\Service\Season;

use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCases\PublicTestCase;

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

    public function setUp(): void
    {
        parent::setUp();

        $this->seasons = collect();
        foreach ($this->seasonAttributes as $attributes) {
            $this->seasons->push(new Season($attributes));
        }
    }
}
