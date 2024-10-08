<?php

namespace App\Console\Commands\Scheduler\Telemetry;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Console\Commands\Scheduler\Telemetry\Measurement\DungeonRouteCount;
use App\Console\Commands\Scheduler\Telemetry\Measurement\MachineStats;
use App\Console\Commands\Scheduler\Telemetry\Measurement\Measurement;
use App\Console\Commands\Scheduler\Telemetry\Measurement\MySqlStats;
use App\Console\Commands\Scheduler\Telemetry\Measurement\QueueSize;
use App\Console\Commands\Scheduler\Telemetry\Measurement\TeamCount;
use App\Console\Commands\Scheduler\Telemetry\Measurement\UserCount;
use Exception;
use InfluxDB\Database;
use TrayLabs\InfluxDB\Facades\InfluxDB;

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
    protected $description = 'Refreshes the telemetry to Grafana';

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

            // Machine
            new MachineStats(),

            // MySql
            new MySqlStats(),
        ];
    }

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(): int
    {
        return $this->trackTime(function () {
            $points = [];

            foreach ($this->measurements as $measurement) {
                $points = array_merge($points, $measurement->getPoints());
            }

            // This function does actually exist
            InfluxDB::writePoints($points, Database::PRECISION_SECONDS);

            return 0;
        });
    }
}
