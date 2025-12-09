<?php

namespace Tests\Unit\App\Logging;

use App\Logging\StructuredLogging;
use Illuminate\Log\LogManager;

class TestableStructuredLogging extends StructuredLogging
{
    /**
     * @param LogManager $logManager
     */
    public function __construct(private readonly LogManager $logManager)
    {
        parent::__construct();
    }

    #[\Override]
    public function start(string $functionName, array $context = [], bool $addContext = true): void
    {
        parent::start($functionName, $context, $addContext);
    }

    #[\Override]
    public function end(string $functionName, array $context = []): void
    {
        parent::end($functionName, $context);
    }

    #[\Override]
    public function debug(string $functionName, array $context = []): void
    {
        parent::debug($functionName, $context);
    }

    #[\Override]
    public function notice(string $functionName, array $context = []): void
    {
        parent::notice($functionName, $context);
    }

    #[\Override]
    public function info(string $functionName, array $context = []): void
    {
        parent::info($functionName, $context);
    }

    #[\Override]
    public function warning(string $functionName, array $context = []): void
    {
        parent::warning($functionName, $context);
    }

    #[\Override]
    public function error(string $functionName, array $context = []): void
    {
        parent::error($functionName, $context);
    }

    #[\Override]
    public function critical(string $functionName, array $context = []): void
    {
        parent::critical($functionName, $context);
    }

    #[\Override]
    public function emergency(string $functionName, array $context = []): void
    {
        parent::emergency($functionName, $context);
    }

    #[\Override]
    protected function getDefaultLoggers(): array
    {
        return [
            $this->logManager,
        ];
    }
}
