<?php

namespace Controller\Api\V1\APICombatLogController;

use App\Models\Dungeon;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\APIPublicTestCase;
use Tests\Traits\ValidatesUrls;

abstract class APICombatLogControllerTestBase extends APIPublicTestCase
{
    use LoadsJsonFiles, ValidatesUrls;

    protected Dungeon $dungeon;

    protected abstract function getDungeonKey(): string;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dungeon = Dungeon::where('key', $this->getDungeonKey())->first();
    }

}
