---
name: structured-logging
description: How the StructuredLogging system works and when to use it — per-service Logging classes, start/end context grouping, log levels, and the Discord error alerting flow. Use when adding logging to a service, creating a new {Service}Logging class, choosing a log level, or tracing what a service did from its log output.
---

# Structured Logging

## Purpose

Every service logs through its own dedicated `Logging` class instead of calling `Log::` or `logger()` directly. The goals:

1. **Traceability** — the logs of a service read as a narrative of what happened (start → detail lines → end with elapsed time), so issues can be traced from log output alone.
2. **Alerting** — anything logged at `error` or above is posted to a Discord channel (via the `discord` log channel), which notifies the maintainer so a fix can be started immediately. Choose levels with this in mind.
3. **Testability** — services depend on a `{Service}LoggingInterface`, so tests mock the logging away (see `tests/Fixtures/LoggingFixtures.php`).

## The base class: `app/Logging/StructuredLogging.php`

Abstract class every concrete Logging class ultimately extends (through `RollbarStructuredLogging`). Key behavior:

### Level methods

`debug`, `notice`, `info`, `warning`, `error`, `critical`, `alert`, `emergency` — all `protected`, all with signature `(string $functionName, array $context = [])`. Concrete Logging classes wrap these in one public method per log event.

Messages are the fully qualified method name (`__METHOD__`), shortened to `ClassName::method` when written. On local (`APP_TYPE=local`) lines are padded per level and prefixed with one `-` per open `start()` group so nested operations indent visually; everywhere else the message stays bare (stable and grep/parse-able) and the nesting depth is emitted as a `depth` context field instead.

### start() / end() — grouped, persistent context

```php
protected function start(string $functionName, array $context = [], bool $addContext = true): void;
protected function end(string $functionName, array $context = []): void;
```

- `start('...Start', $context)` logs at **info**, starts a `Stopwatch` (`app/Logic/Utils/Stopwatch.php`), and registers `$context` as *persistent* context: every subsequent log line from this instance — at any level — automatically includes it, until the matching `end()`.
- The group key is the method name lowercased with the trailing `start`/`end` stripped, so `importStringStart` and `importStringEnd` pair automatically. **Start/End method names must match exactly apart from the suffix.** When `app.debug` is on, a function name that doesn't end in the literal `Start`/`End` throws a `LogicException` (a name like `restart` would silently mispair to group `re`).
- `end('...End')` logs at **info** with an `elapsedMS` context key (from the Stopwatch), then removes the persistent context.
- `start()` on an already-open key or `end()` on a never-opened key logs an **error** (which goes to Discord) — so unbalanced pairs surface loudly. Always call `end()` in a `finally` block, or better: use `wrapLog()` (below), which guarantees the pairing.
- Nesting is supported: a second `start()` inside an open group stacks its context on top; log lines get an extra `-` prefix per nesting level (locally) / a higher `depth` context value (elsewhere).
- Pass `addContext: false` to `start()` to log the context once without attaching it to subsequent lines.
- `addContext(string $key, array ...$context)` / `removeContext(string $key)` (the `StructuredLoggingInterface`) manage persistent context manually, outside a start/end pair.

### wrapLog() — guaranteed start/end pairing

```php
protected function wrapLog(string $functionName, array $context, Closure $callback): mixed;
```

Runs `start()`, the callback, and `end()` in a try/finally, returning the callback result — so a throwing callback can never leave an unbalanced group. `$functionName` is the base name *without* the Start/End suffix; the suffixes are appended internally. Closures in `$context` (the callback that `get_defined_vars()` picks up) are filtered out automatically. The Logging class exposes one public wrapper method, and the service wraps its operation in it:

```php
// In the Logging class (+ its interface, both annotated with @template T / Closure(): T / @return T):
public function getDisplayIdRequest(int $npcId, Closure $callback): mixed
{
    return $this->wrapLog(__METHOD__, get_defined_vars(), $callback);
}

// In the service:
return $this->log->getDisplayIdRequest($npcId, function () use ($npcId): ?int {
    // ... work; every log line in here carries npcId ...
});
```

Prefer `wrapLog()` for new code; migrate manual start/try/finally/end call sites opportunistically.

### Cross-service tracing via Laravel Context

- Persistent `start()` context is mirrored into `Illuminate\Support\Facades\Context` under a `structured:{classname}::{method}` key and removed again on `end()`. Laravel appends Context to **every** log line process-wide, so when ServiceA calls ServiceB, ServiceB's error lines (= Discord alerts) still carry ServiceA's identifying context. Context is also dehydrated into queued job payloads.
- Every request gets a `trace_id` UUID via the `AddsTraceIdToContext` middleware (prepended globally in `bootstrap/app.php`); console commands get theirs from a `CommandStarting` listener in `AppServiceProvider`. One grep on a `trace_id` over `storage/logs/laravel.log` reconstructs the full narrative of a request/command across services and queued jobs.

### Filtering, channels, kill switch

- `shouldLog()` compares against `config('app.log_level')` (`LOG_LEVEL` env, default `debug`). Below-threshold lines are skipped entirely.
- `StructuredLogging::setChannel('stderr')` routes all `LogManager` loggers to a specific channel; the constructor does this automatically for local console runs (not unit tests).
- `StructuredLogging::disable()` / `enable()` is a global static kill switch (used e.g. by long-running commands that would otherwise spam).
- `getDefaultLoggers()` returns `[logger()]`; override it or call `addLogger()` to write to additional PSR-3 loggers. `RollbarStructuredLogging` (`app/Logging/RollbarStructuredLogging.php`) is the hook for Rollbar (currently commented out) — **concrete Logging classes extend this**, not `StructuredLogging` directly.

## Choosing a level

| Level | Use for | Consequence |
|---|---|---|
| `debug` | Detailed tracing data: intermediate values, per-item progress | Only in logs when `LOG_LEVEL=debug` |
| `info` | Milestones — `start()`/`end()` log at this level | Normal log narrative |
| `notice` / `warning` | Suspicious-but-handled situations: fallbacks taken, retries, unexpected-but-recoverable input | Visible in logs, no alert |
| `error` and above | Something is broken and a human should look at it | **Posted to Discord → maintainer is notified** |

Rule of thumb: log at `error`+ only when you would want to be pinged about it. A failed external request that will be retried is a `warning`; a failed request with no recovery path is an `error`. The `discord` channel in `config/logging.php` is a `MarvinLabs\DiscordLogger` custom channel at level `error`, fed by the `APP_LOG_DISCORD_WEBHOOK` env var.

## Per-service convention

Each service directory has a `Logging/` subdirectory with an interface + implementation pair:

```
app/Service/{Domain}/
├── {Name}Service.php
├── {Name}ServiceInterface.php
└── Logging/
    ├── {Name}ServiceLogging.php            — extends RollbarStructuredLogging, implements the interface
    └── {Name}ServiceLoggingInterface.php
```

(The same pattern applies outside services, e.g. `app/Exceptions/Logging/HandlerLogging.php` and the builder/extractor Logging classes under `app/Service/CombatLog/`.)

### The Logging class

One public method **per log event**, named `{callingFunctionName}{WhatHappened}`. The body is a single call to the matching level method with `__METHOD__` and `get_defined_vars()`:

```php
class WowToolsServiceLogging extends RollbarStructuredLogging implements WowToolsServiceLoggingInterface
{
    public function getDisplayIdRequestStart(int $npcId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getDisplayIdRequestError(string $error): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getDisplayIdRequestResult(int $displayInfoId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getDisplayIdRequestEnd(): void
    {
        $this->end(__METHOD__);
    }
}
```

Conventions:
- Context is passed as **typed method parameters**, never as a raw array — `get_defined_vars()` turns them into the context array. This keeps call sites type-safe and the context keys self-documenting.
- Method names ending in `Start`/`End` become a start/end pair (same prefix!).
- The interface mirrors every public method 1:1.
- No logic in Logging classes — they only translate a named event to a level + context.

### Registration

Bind the pair in `app/Providers/LoggingServiceProvider.php` (alphabetical-ish, grouped per domain with a comment):

```php
// WowTools
$this->app->bind(WowToolsServiceLoggingInterface::class, WowToolsServiceLogging::class);
```

### Usage in the service

Inject the interface as a `private readonly` property named `$log`, wrap the operation in start/end with the end in `finally`:

```php
class WowToolsService implements WowToolsServiceInterface
{
    public function __construct(private readonly WowToolsServiceLoggingInterface $log)
    {
    }

    public function getDisplayId(int $npcId): ?int
    {
        $this->log->getDisplayIdRequestStart($npcId);

        try {
            // ... work, calling $this->log->getDisplayIdRequestError(...) etc. on the way
        } finally {
            $this->log->getDisplayIdRequestEnd();
        }

        return $result;
    }
}
```

Because `$npcId` was passed to `...Start()`, every line logged inside the try block automatically carries `npcId` in its context — you never re-pass identifying context to the inner log calls.

## Writing logs that make issues traceable

When adding logging to a service method, cover these paths:

1. **`...Start(...)`** at the top with the identifying inputs (route id, user id, filename, …) — this context tags everything that follows.
2. One event method per **branch that matters**: each failure path, each fallback, each surprising-but-handled case. Name it after what happened (`...InvalidResponse`, `...NotFound`, `...CorruptEvents`).
3. **`...End()`** in `finally` — gives you `elapsedMS` for free and guarantees balanced groups.

The resulting log reads like: `WowToolsServiceLogging::getDisplayIdRequestStart {"npcId":123}` → indented detail lines → `...End {"npcId":123,"elapsedMS":417}`. When an error hits Discord, its context contains the persistent keys from every open `start()` group, so the offending entity is identifiable from the alert alone.

## Testing

- Services under test mock the Logging interface via `tests/Fixtures/LoggingFixtures.php` (`createCombatLogServiceLogging($this)` etc. — add a factory method there for a new Logging interface).
- The base class itself is tested in `tests/Unit/App/Logging/StructuredLoggingTest.php` using `TestableStructuredLogging` (exposes the protected methods) and `LoggingFixtures::createLogManager($this)` (a `LogManager` mock whose `channel()` returns itself).
- Tests that assert log lines are emitted must pin the threshold in Arrange: `config(['app.log_level' => 'debug']);` — otherwise the ambient `LOG_LEVEL` decides whether `log()` fires at all.
