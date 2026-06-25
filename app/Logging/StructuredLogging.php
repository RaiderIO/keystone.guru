<?php

namespace App\Logging;

use App\Logic\Utils\Stopwatch;
use Illuminate\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Monolog\Level;
use Psr\Log\LoggerInterface;

abstract class StructuredLogging implements StructuredLoggingInterface
{
    private static bool $ENABLED = true;

    private static int $GROUPED_CONTEXT_COUNT = 0;

    /** @var array<int, string> Precalculated padding to ensure the log lines line up nicely with some padding at the start */
    private static array $START_PADDING = [
        Level::Debug->value     => '  ',
        Level::Notice->value    => ' ',
        Level::Info->value      => '   ',
        Level::Warning->value   => '',
        Level::Error->value     => '  ',
        Level::Critical->value  => '',
        Level::Alert->value     => '  ',
        Level::Emergency->value => '',
    ];

    private static ?string $CHANNEL = null;

    /** @var array<string, array<string, mixed>> Every begin call that was made, a new key => [] is added to this array. */
    private array $groupedContexts = [];

    /** @var array<string, mixed> Upon calling begin() or end(), this array is a flattened version of to make it quicker to write logs to disk */
    private array $cachedContext = [];

    /** @var bool Optimization to only cache the context when we're actually going to log something - not when adding the context yet */
    private bool $isContextCached = false;

    /** @var array<string, string> When logging, we make conversions like this:
     * App\Service\WowTools\Logging\WowToolsServiceLogging::getDisplayIdRequestError to WowToolsServiceLogging::getDisplayIdRequestError
     * This array caches these conversions to make it quicker to log
     */
    private array $cachedConvertedFunctionNames = [];

    /** @var array<int, LoggerInterface> */
    private array $loggers = [];

    public function __construct()
    {
        /** @var Application $app */
        $app = app();

        if ($app->runningInConsole() && !$app->runningUnitTests() && config('app.type') === 'local') {
            static::setChannel('stderr');
        }

        foreach ($this->getDefaultLoggers() as $defaultLogger) {
            $this->addLogger($defaultLogger);
        }
    }

    /** @param array<string, mixed> ...$context */
    public function addContext(string $key, array ...$context): void
    {
        if (!isset($this->groupedContexts[$key])) {
            self::$GROUPED_CONTEXT_COUNT++;
        }
        $this->groupedContexts[$key] = empty($context) ? [] : array_merge(...$context);
        $this->isContextCached       = false;
    }

    public function removeContext(string $key): void
    {
        if (isset($this->groupedContexts[$key])) {
            self::$GROUPED_CONTEXT_COUNT--;

            unset($this->groupedContexts[$key]);
            $this->isContextCached = false;
        }
    }

    /** @param array<string, mixed> $context */
    protected function start(string $functionName, array $context = [], bool $addContext = true): void
    {
        $level = Level::Info;
        // Do not use $this->shouldLog because the context should still be cached for future log lines that are logged
        // But if the entire Structured Logging is disabled, we don't need it so we just return
        if (!self::$ENABLED) {
            return;
        }

        $targetKey = Str::replaceEnd('start', '', strtolower($functionName));

        if (isset($this->groupedContexts[$targetKey])) {
            $this->log(
                Level::Error,
                sprintf('%s: Unable to start a structured log that was already started!', __METHOD__),
                array_merge(['targetKey' => $targetKey], $context),
            );
        }

        // Sometimes you just want to log something without adding the context to all subsequent log lines
        $this->addContext($targetKey, $addContext ? $context : []);
        Stopwatch::start($targetKey);

        $this->log($level, $functionName, $context);
    }

    /** @param array<string, mixed> $context */
    protected function end(string $functionName, array $context = []): void
    {
        $level = Level::Info;
        if (!self::$ENABLED) {
            return;
        }

        $targetKey = Str::replaceEnd('end', '', strtolower($functionName));

        // Additional check here to prevent doing array_merge if it's not needed
        if ($this->shouldLog($level)) {
            $this->log($level, $functionName, array_merge($context, ['elapsedMS' => Stopwatch::stop($targetKey)]));
        }

        if (!isset($this->groupedContexts[$targetKey])) {
            $this->log(
                Level::Error,
                sprintf("%s: Unable to end a structured log that wasn't started!", __METHOD__),
                array_merge(['targetKey' => $targetKey], $context),
            );
        }

        $this->removeContext($targetKey);
    }

    /** @param array<string, mixed> $context */
    protected function debug(string $functionName, array $context = []): void
    {
        $this->log(Level::Debug, $functionName, $context);
    }

    /** @param array<string, mixed> $context */
    protected function notice(string $functionName, array $context = []): void
    {
        $this->log(Level::Notice, $functionName, $context);
    }

    /** @param array<string, mixed> $context */
    protected function info(string $functionName, array $context = []): void
    {
        $this->log(Level::Info, $functionName, $context);
    }

    /** @param array<string, mixed> $context */
    protected function warning(string $functionName, array $context = []): void
    {
        $this->log(Level::Warning, $functionName, $context);
    }

    /** @param array<string, mixed> $context */
    protected function error(string $functionName, array $context = []): void
    {
        $this->log(Level::Error, $functionName, $context);
    }

    /** @param array<string, mixed> $context */
    protected function critical(string $functionName, array $context = []): void
    {
        $this->log(Level::Critical, $functionName, $context);
    }

    /** @param array<string, mixed> $context */
    protected function alert(string $functionName, array $context = []): void
    {
        $this->log(Level::Alert, $functionName, $context);
    }

    /** @param array<string, mixed> $context */
    protected function emergency(string $functionName, array $context = []): void
    {
        $this->log(Level::Emergency, $functionName, $context);
    }

    /** @return array<int, LoggerInterface> */
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

    /** @param array<string, mixed> $context */
    private function log(Level $level, string $functionName, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        if (!$this->isContextCached) {
            $this->cacheGroupedContexts();
        }

        $levelName = $level->getName();

        // Cache the following operation - it's pretty slow
        if (!isset($this->cachedConvertedFunctionNames[$functionName . self::$GROUPED_CONTEXT_COUNT])) {
            // Convert App\Service\WowTools\Logging\WowToolsServiceLogging::getDisplayIdRequestError to WowToolsServiceLogging::getDisplayIdRequestError
            $this->cachedConvertedFunctionNames[$functionName . self::$GROUPED_CONTEXT_COUNT] = trim(
                sprintf('%s %s', str_repeat('-', self::$GROUPED_CONTEXT_COUNT), array_reverse(explode('\\', $functionName))[0]),
            );
        }
        $messageWithContextCounts = $this->cachedConvertedFunctionNames[$functionName . self::$GROUPED_CONTEXT_COUNT];

        foreach ($this->loggers as $logger) {
            if ($logger instanceof LogManager) {
                $logger = $logger->channel(self::$CHANNEL);
            }

            $logger->log(
                $levelName,
                sprintf('%s%s', self::$START_PADDING[$level->value], $messageWithContextCounts),
                empty($context) ? $this->cachedContext : array_merge($this->cachedContext, $context),
            );
        }
    }

    private function cacheGroupedContexts(): void
    {
        $this->cachedContext = [];

        foreach ($this->groupedContexts as $key => $context) {
            $this->cachedContext = array_merge($this->cachedContext, $context);
        }
        $this->isContextCached = true;
    }

    private function shouldLog(Level $level): bool
    {
        // Higher than does not include the level itself, so negate lower than instead
        return self::$ENABLED && !$level->isLowerThan(self::getLogLevel());
    }

    private static function getLogLevel(): Level
    {
        return Level::fromName(ucfirst(config('app.log_level') ?? 'Debug'));
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
