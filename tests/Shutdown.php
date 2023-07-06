<?php

namespace Tests;

use App\Logic\Utils\Stopwatch;

trait Shutdown
{
    /**
     * @test
     */
    private function shutdown(): void
    {
        Stopwatch::dumpAll();
    }
}
