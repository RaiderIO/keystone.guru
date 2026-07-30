<?php

namespace Tests\Feature\Console\Commands\Scheduler\Fixtures;

use App\Console\Commands\Scheduler\SchedulerCommand;
use Closure;

/**
 * Runs an arbitrary callable through SchedulerCommand::trackTime() so its behaviour can be tested without depending
 * on any of the real scheduler commands.
 */
class SchedulerCommandStub extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:schedulercommandstub';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs an arbitrary callable through trackTime()';

    /** @var Closure(): mixed */
    public static Closure $callable;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->trackTime(self::$callable);
    }
}
