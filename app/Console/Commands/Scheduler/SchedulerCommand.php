<?php

namespace App\Console\Commands\Scheduler;

use App\Console\Commands\Traits\SavesToInfluxDB;
use App\Logic\Utils\Stopwatch;
use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Context;
use Throwable;

abstract class SchedulerCommand extends Command
{
    use SavesToInfluxDB;

    /**
     * @param callable(): mixed $callable A callable returning an exit code - anything that isn't an int counts as success
     */
    public function trackTime(callable $callable): int
    {
        Stopwatch::start(__METHOD__);

        // Prevent long tasks from inserting the point very late
        $startTime = time();

        try {
            $result = $callable();
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            $this->reportThrowable($throwable);

            return self::FAILURE;
        }

        $this->savePointToInfluxDB(
            'scheduler',
            $this->getTags(),
            [$this->getName() => Stopwatch::stop(__METHOD__)],
            $startTime,
        );

        // Callables that return nothing have nothing to complain about
        return is_int($result) ? $result : self::SUCCESS;
    }

    /**
     * Reports a throwable through the application's exception handler, tagged with the command that produced it.
     *
     * The scheduler only ever logs the exit code of a failed command. Without reporting it ourselves, the message and
     * stack trace would only reach the command's stdout, which is a different sink than the one that surfaces the
     * "Scheduled command [..] failed with exit code [1]" line - leaving no way to tell what actually went wrong (#3748).
     *
     * @param array<string, mixed> $context Added to Laravel's Context so it shows up alongside the trace_id
     */
    protected function reportThrowable(Throwable $throwable, array $context = []): void
    {
        $context = array_merge(['command' => (string)$this->getName()], $context);

        foreach ($context as $key => $value) {
            Context::add($key, $value);
        }

        try {
            $this->getLaravel()->make(ExceptionHandler::class)->report($throwable);
        } finally {
            foreach (array_keys($context) as $key) {
                Context::forget($key);
            }
        }
    }
}
