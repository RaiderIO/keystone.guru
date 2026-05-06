<?php

namespace Tests\Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use Tests\TestCases\AjaxPublicTestCase;

abstract class DungeonRouteTestBase extends AjaxPublicTestCase
{
    protected DungeonRoute $dungeonRoute;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->make();

        $this->dungeonRoute = $dungeonRoute;
        $this->dungeonRoute->save();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->dungeonRoute->delete();

        parent::tearDown();
    }
}
