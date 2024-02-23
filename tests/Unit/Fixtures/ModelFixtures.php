<?php

namespace Tests\Unit\Fixtures;

use App\Models\Season;
use Tests\TestCases\PublicTestCase;

class ModelFixtures
{
    /**
     * @param PublicTestCase $testCase
     * @param array          $attributes
     * @return Season
     */
    public static function getSeasonMock(PublicTestCase $testCase, array $attributes): Season
    {
        return $testCase->createMock(Season::class);
    }
}
