<?php

namespace Tests\Feature\App\Service\Season\SeasonService;

use App\Service\Season\SeasonService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetAllSeasons')]
final class GetAllSeasonsTest extends PublicTestCase
{
    #[Test]
    public function getAllSeasons_givenSeasonsAlreadyLoaded_runsNoQuery(): void
    {
        // Arrange
        $service  = app(SeasonService::class);
        $expected = app('model-cache')->runDisabled(static fn() => $service->getAllSeasons()->pluck('id')->all());

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        // Act - CI runs with the model cache on, which would answer these queries without reaching the database
        $seasonIds = app('model-cache')->runDisabled(static fn() => $service->getAllSeasons()->pluck('id')->all());

        // Assert
        $this->assertNotEmpty($seasonIds);
        $this->assertSame(0, $queries);
        $this->assertSame($expected, $seasonIds);
    }
}
