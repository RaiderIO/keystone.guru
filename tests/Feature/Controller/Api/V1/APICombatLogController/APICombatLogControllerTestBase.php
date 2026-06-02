<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController;

use App\Models\Dungeon;
use Tests\Attributes\SlowTest;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\APIPublicTestCase;
use Tests\Traits\ValidatesUrls;

#[SlowTest]
abstract class APICombatLogControllerTestBase extends APIPublicTestCase
{
    use LoadsJsonFiles, ValidatesUrls;

    protected Dungeon $dungeon;

    protected abstract function getDungeonKey(): string;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dungeon = Dungeon::where('key', $this->getDungeonKey())->first();
    }
}
