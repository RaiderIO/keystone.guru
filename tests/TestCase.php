<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use Bootstrap;
    use CreatesApplication;
    use Shutdown;

    protected function setUp(): void
    {
        parent::setUp();

        // Use a hacky global so that we really only execute this once
        global $initialized;

        if (! $initialized) {
            // Do something once here for _all_ test subclasses.
            $initialized = true;

            $this->bootstrap();
        }
    }

    //    protected function tearDown(): void
    //    {
    //        parent::tearDown();
    //
    //        global $initialized;
    //
    //        if ($initialized) {
    //            $initialized = false;
    //
    //            $this->shutdown();
    //        }
    //    }
}
