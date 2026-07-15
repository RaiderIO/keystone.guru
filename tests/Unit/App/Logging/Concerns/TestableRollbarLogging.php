<?php

namespace Tests\Unit\App\Logging\Concerns;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;
use Psr\Log\LoggerInterface;

class TestableRollbarLogging extends StructuredLogging
{
    use InteractsWithRollbar;

    /** @return array<int, LoggerInterface> */
    public function resolveDefaultLoggers(): array
    {
        return $this->getDefaultLoggers();
    }
}
