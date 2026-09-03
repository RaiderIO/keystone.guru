<?php

namespace App\Console\Commands\Scheduler\Telemetry;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Console\Commands\Scheduler\Telemetry\Measurement\DungeonRouteCount;
use App\Console\Commands\Scheduler\Telemetry\Measurement\Measurement;
use App\Console\Commands\Scheduler\Telemetry\Measurement\MySqlStats;
use App\Console\Commands\Scheduler\Telemetry\Measurement\QueueSize;
use App\Console\Commands\Scheduler\Telemetry\Measurement\RedisSize;
use App\Console\Commands\Scheduler\Telemetry\Measurement\TeamCount;
use App\Console\Commands\Scheduler\Telemetry\Measurement\UserCount;
use App\Service\Telemetry\TelemetryServiceInterface;

class Telemetry extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:telemetry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Samples site gauges (user/team/route counts, queue sizes, MySQL stats, Redis DB sizes) into the telemetry metrics store';

    /** @var array|Measurement[] */
    private $measurements;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->measurements = [
            // Site stats
            new UserCount(),
            new TeamCount(),
            new DungeonRouteCount(),
            new QueueSize(),

            // MySql
            new MySqlStats(),

            // Redis
            new RedisSize(),
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(TelemetryServiceInterface $telemetryService): int
    {
        return $this->trackTime(function () use ($telemetryService) {
            $dataPoints = [];

            foreach ($this->measurements as $measurement) {
                $dataPoints = array_merge($dataPoints, $measurement->getDataPoints());
            }

            $telemetryService->recordDataPoints($dataPoints);

            return 0;
        });
    }
}
