<?php

namespace Tests\Unit\App\Logging;

use App\Logging\EventStructuredLogging;
use Illuminate\Log\LogManager;

class TestableEventLogging extends EventStructuredLogging implements TestableEventLoggingInterface
{
    public function __construct(private readonly LogManager $logManager)
    {
        parent::__construct();
    }

    #[\Override]
    protected function getDefaultLoggers(): array
    {
        return [
            $this->logManager,
        ];
    }
}
