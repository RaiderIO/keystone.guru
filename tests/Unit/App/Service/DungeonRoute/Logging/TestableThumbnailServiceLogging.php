<?php

namespace Tests\Unit\App\Service\DungeonRoute\Logging;

use App\Service\DungeonRoute\Logging\ThumbnailServiceLogging;
use Illuminate\Log\LogManager;

class TestableThumbnailServiceLogging extends ThumbnailServiceLogging
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
