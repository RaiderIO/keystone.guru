<?php

namespace App\Logging\Handlers;

use Illuminate\Log\Logger;

class ColoredLineFormatter
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new \Bramus\Monolog\Formatter\ColoredLineFormatter());
        }
    }
}
