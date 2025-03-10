<?php

use App\Models\Metrics\Metric;
use App\Service\Metric\MetricServiceInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $metricsService = app(MetricServiceInterface::class);
        $consoleOutput  = new ConsoleOutput();

        DB::statement('CREATE TABLE IF NOT EXISTS `metrics_temp` LIKE `metrics`');

        DB::statement('ALTER TABLE metrics_temp ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

        $newMetricsAttributes = [];
        $totalMetrics         = Metric::count();
        $processedMetrics     = 0;

        // A little trick since API requests can be compressed a lot more than other metrics, and they
        // are the most numerous
        Metric::orderByDesc('category')
            ->chunk(100000, function (Collection $metrics) use ($metricsService, $consoleOutput, &$newMetricsAttributes, $totalMetrics, &$processedMetrics) {

                $metricsArr = $metrics->map(function (Metric $metric) {
                    $dateTime = $metric->created_at->toDateTimeString();

                    return [
                        'model_id'    => $metric->model_id,
                        'model_class' => $metric->model_class,
                        'category'    => $metric->category,
                        'tag'         => $metric->tag,
                        'value'       => $metric->value,
                        'created_at'  => $dateTime,
                        'updated_at'  => $dateTime,
                    ];
                })->values()->toArray();

                $merged = array_merge($newMetricsAttributes, $metricsArr);

                $newMetricsAttributes = $metricsService->groupMetrics(
                    $merged,
                    60
                );

                $processedMetrics += count($metricsArr);
                $consoleOutput->writeln(sprintf('Processed %d/%d metrics (%d attributes)', $processedMetrics, $totalMetrics, count($newMetricsAttributes)));
            });

        // Sort by created_at, oldest first
        $createdAt = array_column($newMetricsAttributes, 'created_at');
        array_multisort($createdAt, SORT_ASC, $newMetricsAttributes);

        $i = 0;
        foreach ($newMetricsAttributes as &$metric) {
            $metric['id'] = ++$i;
        }

        // Insert into the new table
        if (!empty($newMetricsAttributes)) {
            $total     = count($newMetricsAttributes);
            $processed = 0;
            foreach (array_chunk($newMetricsAttributes, 1000) as $chunk) {
                DB::table('metrics_temp')->insert($chunk);

                if ($processed % 50000 === 0) {
                    $consoleOutput->writeln(sprintf('Inserted %d/%d rows...', $processed, $total));
                }
                $processed += count($chunk);
            }
        }
        $consoleOutput->writeln('Inserting done!');


        DB::statement('RENAME TABLE metrics TO metrics_old, metrics_temp TO metrics;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics_temp');
    }
};
