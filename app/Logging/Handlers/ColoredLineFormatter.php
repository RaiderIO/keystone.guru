<?php

namespace App\Logging\Handlers;

use Illuminate\Log\Logger;
use Monolog\Handler\FormattableHandlerInterface;

class ColoredLineFormatter
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new \Bramus\Monolog\Formatter\ColoredLineFormatter());
            }
        }
    }
}
