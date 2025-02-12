<?php

namespace App\Logging;

use App\Logic\Utils\Stopwatch;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Monolog\Level;
use Psr\Log\LoggerInterface;

abstract class StructuredLogging implements StructuredLoggingInterface
{
    private static bool $ENABLED = true;

    private static int $GROUPED_CONTEXT_COUNT = 0;

    private static ?string $CHANNEL = null;

    /** @var array Every begin call that was made, a new key => [] is added to this array. */
    private array $groupedContexts = [];

    /** @var array Upon calling begin() or end(), this array is a flattened version of to make it quicker to write logs to disk */
    private array $cachedContext = [];

    private ?string $channel = null;

    /** @var LogManager[] */
    private array $loggers = [];

    public function __construct()
    {
        /** @var Application|Container $app */
        $app = app();

        if ($app->runningInConsole() && config('app.type') === 'local') {
            $this->setChannel('stderr');
        }

        foreach ($this->getDefaultLoggers() as $defaultLogger) {
            $this->addLogger($defaultLogger);
        }
    }

    public function addContext(string $key, array ...$context): void
    {
        if (!isset($this->groupedContexts[$key])) {
            self::$GROUPED_CONTEXT_COUNT++;
        }
        // Add all variables from $context, but remove key (our first parameter) since we don't need it
        $this->groupedContexts[$key] = empty($context) ? [] : array_merge(...$context);
        $this->cacheGroupedContexts();
    }

    public function removeContext(string $key): void
    {
        if (isset($this->groupedContexts[$key])) {
            self::$GROUPED_CONTEXT_COUNT--;

            unset($this->groupedContexts[$key]);
            $this->cacheGroupedContexts();
        }
    }

    protected function start(string $functionName, array $context = [], bool $addContext = true): void
    {
        $targetKey = Str::replaceEnd('start', '', strtolower($functionName));

        if (isset($this->groupedContexts[$targetKey])) {
            $this->log(
                Level::Error,
                sprintf('%s: Unable to start a structured log that was already started!', __METHOD__),
                array_merge(['targetKey' => $targetKey], $context)
            );
        }

        // Sometimes you just want to log something without adding the context to all subsequent log lines
        $this->addContext($targetKey, $addContext ? $context : []);
        Stopwatch::start($targetKey);

        $this->log(Level::Info, $functionName, $context);
    }

    protected function end(string $functionName, array $context = []): void
    {
        $targetKey = Str::replaceEnd('end', '', strtolower($functionName));

        $this->log(Level::Info, $functionName, array_merge($context, ['elapsedMS' => Stopwatch::stop($targetKey)]));

        if (!isset($this->groupedContexts[$targetKey])) {
            $this->log(
                Level::Error,
                sprintf("%s: Unable to end a structured log that wasn't started!", __METHOD__),
                array_merge(['targetKey' => $targetKey], $context)
            );
        }

        $this->removeContext($targetKey);
    }

    protected function debug(string $functionName, array $context = []): void
    {
        $this->log(Level::Debug, $functionName, $context);
    }

    protected function notice(string $functionName, array $context = []): void
    {
        $this->log(Level::Notice, $functionName, $context);
    }

    protected function info(string $functionName, array $context = []): void
    {
        $this->log(Level::Info, $functionName, $context);
    }

    protected function warning(string $functionName, array $context = []): void
    {
        $this->log(Level::Warning, $functionName, $context);
    }

    protected function error(string $functionName, array $context = []): void
    {
        $this->log(Level::Error, $functionName, $context);
    }

    protected function critical(string $functionName, array $context = []): void
    {
        $this->log(Level::Critical, $functionName, $context);
    }

    protected function alert(string $functionName, array $context = []): void
    {
        $this->log(Level::Alert, $functionName, $context);
    }

    protected function emergency(string $functionName, array $context = []): void
    {
        $this->log(Level::Emergency, $functionName, $context);
    }

    protected function getDefaultLoggers(): array
    {
        return [
            logger(),
        ];
    }

    protected function addLogger(LoggerInterface $logger): void
    {
        $this->loggers[] = $logger;
    }

    private function log(Level $level, string $functionName, array $context = []): void
    {
        if (!self::$ENABLED) {
            return;
        }

        $levelName = $level->getName();
        // WARNING = 7, yeah I know EMERGENCY is 9 but that's used so little that I'm not compensating for it
        $fixedLength     = 7;
        $levelNameLength = strlen($levelName);
        $startPadding    = str_repeat(' ', max(0, $fixedLength - $levelNameLength));

        $messageWithContextCounts = trim(
            sprintf('%s %s', str_repeat('-', self::$GROUPED_CONTEXT_COUNT), array_reverse(explode('\\', $functionName))[0])
        );
        // Convert App\Service\WowTools\Logging\WowToolsServiceLogging::getDisplayIdRequestError to WowToolsServiceLogging::getDisplayIdRequestError

        foreach ($this->loggers as $logger) {
            if ($logger instanceof LogManager) {
                $logger = $logger->channel(self::$CHANNEL);
            }

            $logger->log(
                $levelName,
                sprintf('%s%s', $startPadding, $messageWithContextCounts),
                array_merge($this->cachedContext, $context)
            );
        }
    }

    private function cacheGroupedContexts(): void
    {
        $this->cachedContext = [];

        foreach ($this->groupedContexts as $key => $context) {
            $this->cachedContext = array_merge($this->cachedContext, $context);
        }
    }

    public static function setChannel(?string $channel): void
    {
        self::$CHANNEL = $channel;
    }

    public static function getChannel(): ?string
    {
        return self::$CHANNEL;
    }

    public static function enable(): void
    {
        self::$ENABLED = true;
    }

    public static function disable(): void
    {
        self::$ENABLED = false;
    }
}
