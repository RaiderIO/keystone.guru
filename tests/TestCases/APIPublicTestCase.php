<?php

namespace Tests\TestCases;

use Tests\Feature\Traits\APIAuthentication;

abstract class APIPublicTestCase extends PublicTestCase
{
    use APIAuthentication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addAuthentication();
    }
}
