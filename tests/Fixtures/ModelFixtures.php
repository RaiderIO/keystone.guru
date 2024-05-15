<?php

namespace Tests\Fixtures;

use App\Models\Season;
use Tests\TestCases\PublicTestCase;

class ModelFixtures
{
    public static function getSeasonMock(PublicTestCase $testCase): Season
    {
        return $testCase->createMock(Season::class);
    }
}
