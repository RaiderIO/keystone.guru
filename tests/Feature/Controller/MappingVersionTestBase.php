<?php

namespace Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use Tests\TestCases\AjaxPublicTestCase;

final class MappingVersionTestBase extends AjaxPublicTestCase
{
    protected MappingVersion $mappingVersion;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::factory()->make();

        $this->mappingVersion = $mappingVersion;
        $this->mappingVersion->save();
    }


    protected function tearDown(): void
    {
        $this->mappingVersion->delete();

        parent::tearDown();
    }

}
