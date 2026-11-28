<?php

namespace Tests;

use App\Logic\Utils\Stopwatch;

trait Shutdown
{
    private function shutdown(): void
    {
        Stopwatch::dumpAll();
    }
}
