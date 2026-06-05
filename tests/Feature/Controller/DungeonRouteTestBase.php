<?php

namespace Tests\Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\AjaxPublicTestCase;

abstract class DungeonRouteTestBase extends AjaxPublicTestCase
{
    use GeneratesDungeonRoutes;

    protected DungeonRoute $dungeonRoute;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $this->dungeonRoute->save();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->dungeonRoute->delete();

        parent::tearDown();
    }
}
