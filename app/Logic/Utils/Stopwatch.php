<?php

namespace App\Logic\Utils;

class Stopwatch
{
    /**
     * @var array The start times of the StopWatches
     */
    private static array $timers = [];

    private static function _getTime(): float
    {
        return microtime(true);
    }

    /**
     * Start a timer.
     *
     * @param  $timerName  string The name of the timer
     */
    public static function start(string $timerName = 'default'): void
    {
        // Do not use a $now variable to make the results as accurate as possible, the latest possible moment
        // to grab the time is best.

        // Resuming a paused timer
        if (isset(self::$timers[$timerName]['end'])) {
            // Add the difference to the start to simulate the pause!
            self::$timers[$timerName]['start'] += (self::_getTime() - self::$timers[$timerName]['end']);
            self::$timers[$timerName]['count']++;

            // Remove the pause, it's now applied to the start so it's processed.
            unset(self::$timers[$timerName]['end']);
        } // Create a new timer instead; user wants to discard what was there
        else {
            self::$timers[$timerName] = ['start' => self::_getTime(), 'count' => 1];
        }

        \Debugbar::startMeasure($timerName);
    }

    /**
     * Pause a timer.
     *
     * @param  $timerName  string The name of the timer.
     */
    public static function pause(string $timerName = 'default'): void
    {
        // Do this first to interfere as least as possible with an IF down here
        // Timer is already running at this point, grab the time as quick as we can
        $now = self::_getTime();
        // Prevent double pauses overwriting the first pause
        if (!isset(self::$timers[$timerName]['end'])) {
            self::$timers[$timerName]['end'] = $now;
        }

        \Debugbar::stopMeasure($timerName);
    }

    /**
     * Get the elapsed time in seconds
     *
     * @param  $timerName  string The name of the timer to start
     * @return float The elapsed time since start() was called
     */
    public static function elapsed(string $timerName = 'default'): float
    {
        // We've now ended, grab time asap
        $now        = self::_getTime();
        $timerStart = $now;
        // If timer is not set just return 0
        if (isset(self::$timers[$timerName])) {
            $timerStart = self::$timers[$timerName]['start'];
            // If there's an end, add the difference to the timerStart to make sure the pause is not counted.
            if (isset(self::$timers[$timerName]['end'])) {
                $timerStart += ($now - self::$timers[$timerName]['end']);
            }
        }

        return round(($now - $timerStart) * 1000, 4);
    }

    private static function getElapsedString(string $timerName): string
    {
        return sprintf('Elapsed time%s: %s ms', $timerName === 'default' ? '' : sprintf(' (%s)', $timerName), Stopwatch::elapsed($timerName));
    }

    /**
     * Echoes a timer into the webpage for debugging purposes
     *
     * @param string $timerName The name of the timer that you want to echo.
     */
    public static function dump(string $timerName = 'default'): void
    {
        dump(sprintf('%s (%sx)', self::getElapsedString($timerName), self::$timers[$timerName]['count']));
    }

    public static function stop(string $timerName = 'default'): float
    {
        $elapsed = self::elapsed($timerName);

        unset(self::$timers[$timerName]);
        \Debugbar::stopMeasure($timerName);

        return $elapsed;
    }

    public static function dumpAll(): void
    {
        foreach (self::$timers as $key => $value) {
            self::dump($key);
        }
    }

    public static function getAll(): array
    {
        $result = [];
        foreach (self::$timers as $key => $value) {
            $result[$key] = self::getElapsedString($key);
        }

        return $result;
    }
}
