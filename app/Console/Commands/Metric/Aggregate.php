<?php

namespace App\Console\Commands\Metric;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Class Aggregate
 * @package App\Console\Commands\Localization
 * @author Wouter
 * @since 16/02/2023
 */
class Aggregate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metric:aggregate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates all metrics and writes them to the metric aggregations table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // @TODO move to service
        DB::insert("
            INSERT INTO metric_aggregations (model_id, model_class, category, tag, value, created_at, updated_at)
            SELECT model_id, model_class, category, tag, value, created_at, updated_at
            FROM (
                SELECT IF(model_id is null, -1, model_id) as model_id,
                       IF(model_class is null, '', model_class) as model_class,
                       category,
                       tag,
                       count(0) as value,
                       NOW() as created_at,
                       NOW() as updated_at
                FROM metrics
                GROUP BY model_id, model_class, category, tag
            ) as metrics
        ON DUPLICATE KEY UPDATE metric_aggregations.value = metrics.value, metric_aggregations.updated_at = metrics.updated_at;
        ");

        return 0;
    }
}
