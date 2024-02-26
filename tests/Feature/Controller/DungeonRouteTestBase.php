<?php

namespace Tests\Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use Tests\TestCases\AjaxPublicTestCase;

final class DungeonRouteTestBase extends AjaxPublicTestCase
{
    protected DungeonRoute $dungeonRoute;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->make();

        $this->dungeonRoute = $dungeonRoute;
        $this->dungeonRoute->save();
    }

    protected function tearDown(): void
    {
        $this->dungeonRoute->delete();

        parent::tearDown();
    }
}
