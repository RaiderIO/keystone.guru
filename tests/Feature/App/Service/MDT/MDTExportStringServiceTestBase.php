<?php

namespace Tests\Feature\App\Service\MDT;

use App\Console\Commands\Traits\ConvertsMDTStrings;
use App\Logic\MDT\Conversion;
use App\Models\DungeonRoute\DungeonRoute;
use Tests\TestCases\PublicTestCase;

abstract class MDTExportStringServiceTestBase extends PublicTestCase
{
    use ConvertsMDTStrings;

    protected function getMDTCompatibleDungeonRoute(array $attributes = []): DungeonRoute
    {
        do {
            /** @var DungeonRoute $dungeonRoute */
            $dungeonRoute = DungeonRoute::factory()->create(array_merge([
                'expires_at' => now()->addHour(),
            ], $attributes));
        } while (!Conversion::hasMDTDungeonName($dungeonRoute->dungeon->key));

        return $dungeonRoute;
    }
}
