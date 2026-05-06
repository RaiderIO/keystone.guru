<?php

namespace Tests\Fixtures;

use App\Models\Season;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

class ModelFixtures
{
    public static function getSeasonMock(PublicTestCase $testCase): Season|MockObject
    {
        return $testCase->createMockPublic(Season::class);
    }
}
