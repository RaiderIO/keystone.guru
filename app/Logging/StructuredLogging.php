<?php

namespace App\Logging;

use App\Logic\Utils\Stopwatch;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use LogicException;
use Monolog\Level;
use Psr\Log\LoggerInterface;

abstract class StructuredLogging implements StructuredLoggingInterface
{
    /** Prefix for grouped contexts that are mirrored into Laravel's Context so they never clash with other Context keys */
    private const string GLOBAL_CONTEXT_KEY_PREFIX = 'structured:';

    private static bool $ENABLED = true;

    /** @var Level|null Cached parsed app.log_level - parsing it through Level::fromName on every log call is expensive */
    private static ?Level $LOG_LEVEL = null;

    /** @var bool|null Cached app.type === 'local' check - decides between pretty (aligned/indented) and stable (grep/parse-able) messages */
    private static ?bool $PRETTY_PRINT = null;

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

    /** @var array<int, LoggerInterface>|null Cached result of resolving LogManager loggers to their channel - re-resolving the channel on every log call is expensive */
    private ?array $resolvedLoggers = null;

    /** @var string|null The channel $resolvedLoggers was resolved for, so a setChannel() call invalidates the cache */
    private ?string $resolvedLoggersChannel = null;

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
        $mergedContext = empty($context) ? [] : array_merge(...$context);

        $this->groupedContexts[$key] = $mergedContext;
        $this->isContextCached       = false;

        // Mirror the context into Laravel's Context so log lines of _other_ services (and queued jobs dispatched
        // from here) carry it too - an error that alerts us then always identifies the outer operation
        if (self::$ENABLED && !empty($mergedContext)) {
            Context::add(self::getGlobalContextKey($key), $mergedContext);
        }
    }

    public function removeContext(string $key): void
    {
        if (isset($this->groupedContexts[$key])) {
            unset($this->groupedContexts[$key]);
            $this->isContextCached = false;
        }

        Context::forget(self::getGlobalContextKey($key));
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

        $this->assertFunctionNameEndsWith($functionName, 'Start');

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

        $this->assertFunctionNameEndsWith($functionName, 'End');

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

    /**
     * Wraps $callback in a start()/end() pair, guaranteeing the end() is called even when the callback throws.
     * $functionName should be the base name without the Start/End suffix (usually __METHOD__ of the wrapper method).
     *
     * @template T
     *
     * @param array<string, mixed> $context
     * @param Closure(): T         $callback
     *
     * @return T
     */
    protected function wrapLog(string $functionName, array $context, Closure $callback): mixed
    {
        // get_defined_vars() at the call site includes the callback itself - that should never end up in the context
        $context = array_filter($context, static fn($value) => !$value instanceof Closure);

        $this->start(sprintf('%sStart', $functionName), $context);

        try {
            return $callback();
        } finally {
            $this->end(sprintf('%sEnd', $functionName));
        }
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
        $this->loggers[]       = $logger;
        $this->resolvedLoggers = null;
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

        $groupedContextCount = count($this->groupedContexts);

        $prettyPrint = self::shouldPrettyPrint();

        // Cache the following operation - it's pretty slow
        $cacheKey = sprintf('%d-%s', $prettyPrint ? $groupedContextCount : 0, $functionName);
        if (!isset($this->cachedConvertedFunctionNames[$cacheKey])) {
            // Convert App\Service\WowTools\Logging\WowToolsServiceLogging::getDisplayIdRequestError to WowToolsServiceLogging::getDisplayIdRequestError
            $shortFunctionName = array_reverse(explode('\\', $functionName))[0];

            // Locally the depth is baked into the message for pretty, indented output - everywhere else the message
            // must stay stable and grep/parse-able, so the depth is emitted as a context field instead
            $this->cachedConvertedFunctionNames[$cacheKey] = $prettyPrint
                ? trim(sprintf('%s %s', str_repeat('-', $groupedContextCount), $shortFunctionName))
                : $shortFunctionName;
        }
        $message = $this->cachedConvertedFunctionNames[$cacheKey];

        $context = empty($context) ? $this->cachedContext : array_merge($this->cachedContext, $context);
        if ($prettyPrint) {
            $message = sprintf('%s%s', self::$START_PADDING[$level->value], $message);
        } else {
            $context['depth'] = $groupedContextCount;
        }

        foreach ($this->resolveLoggers() as $logger) {
            $logger->log($levelName, $message, $context);
        }
    }

    /** @return array<int, LoggerInterface> */
    private function resolveLoggers(): array
    {
        if ($this->resolvedLoggers === null || $this->resolvedLoggersChannel !== self::$CHANNEL) {
            $this->resolvedLoggers = [];
            foreach ($this->loggers as $logger) {
                $this->resolvedLoggers[] = $logger instanceof LogManager ? $logger->channel(self::$CHANNEL) : $logger;
            }
            $this->resolvedLoggersChannel = self::$CHANNEL;
        }

        return $this->resolvedLoggers;
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

    /**
     * start()/end() pair up by stripping the Start/End suffix from the function name - a method that doesn't follow
     * the naming convention (e.g. restart) would silently mispair, so fail loudly during development.
     */
    private function assertFunctionNameEndsWith(string $functionName, string $suffix): void
    {
        if (!str_ends_with($functionName, $suffix) && config('app.debug')) {
            throw new LogicException(sprintf('Structured log function name %s must end with "%s"!', $functionName, $suffix));
        }
    }

    private static function getGlobalContextKey(string $key): string
    {
        // Keys coming from start() are lowercased fully qualified method names - keep just the class::method part for readability
        return sprintf('%s%s', self::GLOBAL_CONTEXT_KEY_PREFIX, Str::afterLast($key, '\\'));
    }

    private static function getLogLevel(): Level
    {
        return self::$LOG_LEVEL ??= Level::fromName(ucfirst(config('app.log_level') ?? 'Debug'));
    }

    private static function shouldPrettyPrint(): bool
    {
        return self::$PRETTY_PRINT ??= config('app.type') === 'local';
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

        // The config may have changed while logging was disabled (or between tests) - re-read it lazily
        self::flushConfigCache();
    }

    public static function disable(): void
    {
        self::$ENABLED = false;
    }

    /** Flushes the cached app.log_level/app.type config values so they are re-read on the next log call */
    public static function flushConfigCache(): void
    {
        self::$LOG_LEVEL    = null;
        self::$PRETTY_PRINT = null;
    }
}
