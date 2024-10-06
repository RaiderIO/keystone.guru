<?php

namespace App\Console\Commands\Scheduler;

use App\Console\Commands\Traits\SavesToInfluxDB;
use App\Logic\Utils\Stopwatch;
use Illuminate\Console\Command;

abstract class SchedulerCommand extends Command
{
    use SavesToInfluxDB;

    public function trackTime(callable $callable): int
    {
        Stopwatch::start(__METHOD__);

        try {
            $callable();
        } catch (\Exception $ex) {
            $this->error($ex->getMessage());

            return 1;
        }

        $this->savePointToInfluxDB(
            'scheduler',
            $this->getTags(),
            [$this->getName() => Stopwatch::stop(__METHOD__)],
            time()
        );

        return 0;
    }
}
