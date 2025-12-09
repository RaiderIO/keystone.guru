<?php

namespace Tests\Feature\Controller;

use App\Models\Mapping\MappingVersion;
use Tests\TestCases\AjaxPublicTestCase;

final class MappingVersionTestBase extends AjaxPublicTestCase
{
    protected MappingVersion $mappingVersion;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::factory()->make();

        $this->mappingVersion = $mappingVersion;
        $this->mappingVersion->save();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->mappingVersion->delete();

        parent::tearDown();
    }
}
