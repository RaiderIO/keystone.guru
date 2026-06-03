<?php

namespace Tests\Feature\App\Service\MDT;

use App\Console\Commands\Traits\ConvertsMDTStrings;
use App\Logic\MDT\Conversion;
use App\Models\DungeonRoute\DungeonRoute;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\PublicTestCase;

abstract class MDTExportStringServiceTestBase extends PublicTestCase
{
    use ConvertsMDTStrings;
    use GeneratesDungeonRoutes;
}
