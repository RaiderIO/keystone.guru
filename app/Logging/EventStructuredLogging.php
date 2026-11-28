<?php

namespace App\Logging;

use App\Logging\Attributes\LogEvent;
use BadMethodCallException;
use Monolog\Level;
use ReflectionClass;

/**
 * StructuredLogging whose events are declared once, on the interface, instead of once per method on the interface
 * and once on the implementation: a #[LogEvent] attribute gives the name and level, a @method annotation gives the
 * typed signature. The implementation is an empty subclass.
 *
 * Events must be called with named arguments - the argument names become the context keys (and the Sentry
 * fingerprint keys such as exceptionClass), exactly as the parameter names do for a hand-written method.
 */
abstract class EventStructuredLogging extends StructuredLogging implements EventStructuredLoggingInterface
{
    /** @var array<class-string, array<string, Level>> Event name => level, per concrete class. Immutable once built, so safe under Octane. */
    private static array $eventLevels = [];

    /**
     * @param array<int|string, mixed> $arguments
     */
    public function __call(string $name, array $arguments): void
    {
        $level = self::resolveEventLevels(static::class)[$name] ?? null;
        if ($level === null) {
            throw new BadMethodCallException(sprintf('%s has no #[LogEvent] named "%s"', static::class, $name));
        }

        $functionName = sprintf('%s::%s', static::class, $name);
        $context      = $this->toContext($name, $arguments);

        if (str_ends_with($name, 'Start')) {
            $this->start($functionName, $context);
        } elseif (str_ends_with($name, 'End')) {
            $this->end($functionName, $context);
        } else {
            match ($level) {
                Level::Debug     => $this->debug($functionName, $context),
                Level::Info      => $this->info($functionName, $context),
                Level::Notice    => $this->notice($functionName, $context),
                Level::Warning   => $this->warning($functionName, $context),
                Level::Error     => $this->error($functionName, $context),
                Level::Critical  => $this->critical($functionName, $context),
                Level::Alert     => $this->alert($functionName, $context),
                Level::Emergency => $this->emergency($functionName, $context),
            };
        }
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    private function toContext(string $name, array $arguments): array
    {
        if (!array_is_list($arguments) || empty($arguments)) {
            return $arguments;
        }

        if (config('app.debug')) {
            throw new BadMethodCallException(sprintf('%s::%s must be called with named arguments', static::class, $name));
        }

        return array_combine(array_map(static fn(int $index) => sprintf('argument%d', $index), array_keys($arguments)), $arguments);
    }

    /**
     * @param class-string $class
     *
     * @return array<string, Level>
     */
    private static function resolveEventLevels(string $class): array
    {
        if (isset(self::$eventLevels[$class])) {
            return self::$eventLevels[$class];
        }

        $levels = [];
        foreach ((new ReflectionClass($class))->getInterfaces() as $interface) {
            foreach ($interface->getAttributes(LogEvent::class) as $attribute) {
                $event                = $attribute->newInstance();
                $levels[$event->name] = $event->level;
            }
        }

        return self::$eventLevels[$class] = $levels;
    }
}
