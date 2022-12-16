<?php

namespace App\Logging;

use App\Logic\Utils\Stopwatch;
use Monolog\Logger;

class StructuredLogging
{
    /** @var array Every begin call that was made, a new key => [] is added to this array. */
    private array $groupedContexts = [];

    /** @var array Upon calling begin() or end(), this array is a flattened versino of $groupedContext to make it quicker to write logs to disk */
    private array $cachedContext = [];

    /**
     * @param string $functionName
     * @param array $context
     * @return void
     */
    protected function start(string $functionName, array $context = []): void
    {
        $targetKey = str_replace('start', '', strtolower($functionName));
        if (isset($this->groupedContexts[$targetKey])) {
            $this->log(
                Logger::ERROR,
                sprintf('%s: Unable to start a structured log that was already started!', __METHOD__),
                array_merge(['targetKey' => $targetKey], $context)
            );
        }
        $this->groupedContexts[$targetKey] = $context;
        $this->cachedContext               = call_user_func_array('array_merge', $this->groupedContexts);
        Stopwatch::start($targetKey);

        $this->log(Logger::INFO, $functionName, $context);
    }

    /**
     * @param string $functionName
     * @param array $context
     * @return void
     */
    protected function end(string $functionName, array $context = []): void
    {
        $targetKey = str_replace('end', '', strtolower($functionName));

        $this->log(Logger::INFO, $functionName, array_merge($context, ['elapsedMS' => Stopwatch::stop($targetKey)]));

        if (!isset($this->groupedContexts[$targetKey])) {
            $this->log(
                Logger::ERROR,
                sprintf('%s: Unable to end a structured log that wasn\'t started!', __METHOD__),
                array_merge(['targetKey' => $targetKey], $context)
            );
        }

        unset($this->groupedContexts[$targetKey]);
        $this->cachedContext = call_user_func_array('array_merge', $this->groupedContexts);
    }

    /**
     * @param string $functionName
     * @param array $context
     * @return void
     */
    protected function debug(string $functionName, array $context = []): void
    {
        $this->log(Logger::DEBUG, $functionName, $context);
    }

    /**
     * @param string $functionName
     * @param array $context
     * @return void
     */
    protected function notice(string $functionName, array $context = []): void
    {
        $this->log(Logger::NOTICE, $functionName, $context);
    }

    /**
     * @param string $functionName
     * @param array $context
     * @return void
     */
    protected function info(string $functionName, array $context = []): void
    {
        $this->log(Logger::INFO, $functionName, $context);
    }

    /**
     * @param string $functionName
     * @param array $context
     * @return void
     */
    protected function warning(string $functionName, array $context = []): void
    {
        $this->log(Logger::WARNING, $functionName, $context);
    }

    /**
     * @param string $functionName
     * @param array $context
     * @return void
     */
    protected function error(string $functionName, array $context = []): void
    {
        $this->log(Logger::ERROR, $functionName, $context);
    }

    /**
     * @param string $functionName
     * @param array $context
     * @return void
     */
    protected function critical(string $functionName, array $context = []): void
    {
        $this->log(Logger::CRITICAL, $functionName, $context);
    }

    /**
     * @param string $functionName
     * @param array $context
     * @return void
     */
    protected function emergency(string $functionName, array $context = []): void
    {
        $this->log(Logger::EMERGENCY, $functionName, $context);
    }

    /**
     * @param int $level
     * @param string $functionName
     * @param array $context
     * @return void
     */
    private function log(int $level, string $functionName, array $context = []): void
    {
        // Convert App\Service\WowTools\Logging\WowToolsServiceLogging::getDisplayIdRequestError to WowToolsServiceLogging::getDisplayIdRequestError
        logger()->log(Logger::getLevelName($level), array_reverse(explode('\\', $functionName))[0], array_merge($this->cachedContext, $context));
    }
}
