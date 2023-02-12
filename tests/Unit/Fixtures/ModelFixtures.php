<?php

namespace Fixtures;

use App\Models\Season;
use Tests\PublicTestCase;

class ModelFixtures
{
    public static function getSeasonMock(PublicTestCase $testCase, array $attributes): Season
    {
        return $testCase->createMock(Season::class);
    }
}
