<?php


namespace App\Logic\Utils;

class Counter
{
    /**
     * @var $counters array The start times of the StopWatches
     */
    private static array $counters = [];

    /**
     * @param $counterName
     * @return string
     */
    private static function getCountString($counterName): string
    {
        return sprintf('%s %dx', $counterName, self::$counters[$counterName]);
    }

    /**
     * Increase a new or existing counter.
     *
     * @param string $counterName
     */
    public static function increase(string $counterName)
    {
        // Resuming a paused timer
        if (isset(self::$counters[$counterName])) {
            self::$counters[$counterName]++;
        } // Create a new timer instead; user wants to discard what was there
        else {
            self::$counters[$counterName] = 1;
        }
    }

    /**
     * Echoes a timer into the webpage for debugging purposes
     *
     * @param string $counterName The name of the timer that you want to echo.
     */
    public static function dump(string $counterName = 'default'): void
    {
        dump(self::getCountString($counterName));
    }

    /**
     *
     */
    public static function dumpAll(): void
    {
        foreach (self::$counters as $key => $value) {
            self::dump($key);
        }
    }

    /**
     * @return array
     */
    public static function getAll(): array
    {
        $result = [];
        foreach (self::$counters as $name => $count) {
            $result[$name] = self::getCountString($name);
        }
        return $result;
    }
}
