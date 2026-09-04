<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminToolsTelemetryDataRequest;
use App\Http\Requests\AdminToolsTelemetryRequest;
use App\Models\Telemetry\TelemetryMetric;
use App\Repositories\Interfaces\Telemetry\TelemetryMetricRepositoryInterface;
use App\Service\Telemetry\Dtos\TelemetryCatalogEntry;
use App\Service\Telemetry\Dtos\TelemetrySeries;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Lang;
use Illuminate\View\View;

/**
 * Graphs the operational time series that `SchedulerCommand::trackTime()` and `scheduler:telemetry` write into
 * `telemetry_metrics` (#4075).
 *
 * The chart list is derived from the data rather than hardcoded: a measurement added to `scheduler:telemetry`
 * later gets its chart here as soon as it has recorded a single data point, with no wiring in this class.
 */
class AdminToolsTelemetryController extends Controller
{
    public function index(AdminToolsTelemetryRequest $request, TelemetryMetricRepositoryInterface $telemetryMetricRepository): View
    {
        $catalog = $telemetryMetricRepository->getCatalog($request->getFrom());

        return view('admin.tools.telemetry', [
            'range'             => $request->getRange(),
            'ranges'            => AdminToolsTelemetryRequest::getRanges(),
            'schedulerCommands' => $this->getSchedulerCommands($catalog),
            'gaugeMeasurements' => $this->getGaugeMeasurements($catalog),
        ]);
    }

    public function data(AdminToolsTelemetryDataRequest $request, TelemetryMetricRepositoryInterface $telemetryMetricRepository): JsonResponse
    {
        $measurement       = $request->getMeasurement();
        $name              = $request->getName();
        $from              = $request->getFrom();
        $bucketSizeMinutes = $request->getBucketSizeMinutes();

        $series = $telemetryMetricRepository->getSeries($measurement, $name, $from, $bucketSizeMinutes);
        $labels = $this->getLabels($series);

        return new JsonResponse([
            'labels' => $labels,
            // Only a rolled-up bucket holds more than one sample, so only then does a maximum say anything the
            // average does not - the client uses this to decide whether to draw the second line.
            'rollup'        => $bucketSizeMinutes > 1,
            'datasets'      => $this->getDatasets($series, $labels),
            'failureLabels' => $measurement === TelemetryMetric::MEASUREMENT_SCHEDULER
                ? $telemetryMetricRepository->getFailureBuckets($measurement, $name, $from, $bucketSizeMinutes)
                : [],
        ]);
    }

    /**
     * Every scheduled command with a run in range. Each gets its own chart: run durations of different commands
     * range from milliseconds to minutes, which one shared axis cannot show usefully.
     *
     * @param array<int, TelemetryCatalogEntry> $catalog
     *
     * @return array<int, string>
     */
    private function getSchedulerCommands(array $catalog): array
    {
        $names = [];
        foreach ($catalog as $entry) {
            if ($entry->measurement === TelemetryMetric::MEASUREMENT_SCHEDULER) {
                $names[$entry->name] = true;
            }
        }

        return array_keys($names);
    }

    /**
     * Every non-scheduler measurement with data in range, mapped to its chart title. Each becomes one chart
     * holding all of its names and tags as separate lines - the gauges within a measurement share a unit, so
     * they compare on one axis.
     *
     * A measurement without a translation falls back to its own key rather than rendering a missing one: a gauge
     * added to `scheduler:telemetry` must graph itself here without needing a translation first.
     *
     * @param array<int, TelemetryCatalogEntry> $catalog
     *
     * @return array<string, string> measurement => title
     */
    private function getGaugeMeasurements(array $catalog): array
    {
        $measurements = [];
        foreach ($catalog as $entry) {
            if ($entry->measurement === TelemetryMetric::MEASUREMENT_SCHEDULER) {
                continue;
            }

            $translationKey = sprintf('view_admin.tools.telemetry.measurement.%s', $entry->measurement);

            $measurements[$entry->measurement] = Lang::has($translationKey)
                ? __($translationKey)
                : $entry->measurement;
        }

        return $measurements;
    }

    /**
     * The shared category axis of a chart: every bucket any of its series recorded a value in.
     *
     * @param array<int, TelemetrySeries> $series
     *
     * @return array<int, string>
     */
    private function getLabels(array $series): array
    {
        $labels = [];
        foreach ($series as $singleSeries) {
            foreach ($singleSeries->buckets as $bucket) {
                $labels[$bucket] = true;
            }
        }

        $labels = array_keys($labels);
        sort($labels);

        return $labels;
    }

    /**
     * Aligns every series onto the shared axis, leaving a null wherever a series has no value - a gauge that
     * only started being recorded halfway through the range must not shift the rest of its line sideways.
     *
     * @param array<int, TelemetrySeries> $series
     * @param array<int, string>          $labels
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDatasets(array $series, array $labels): array
    {
        $labelIndexes = array_flip($labels);

        $datasets = [];
        foreach ($series as $singleSeries) {
            $averages = array_fill(0, count($labels), null);
            $maximums = array_fill(0, count($labels), null);

            foreach ($singleSeries->buckets as $index => $bucket) {
                $averages[$labelIndexes[$bucket]] = $singleSeries->averages[$index];
                $maximums[$labelIndexes[$bucket]] = $singleSeries->maximums[$index];
            }

            $datasets[] = [
                'label'   => $singleSeries->getLabel(),
                'average' => $averages,
                'maximum' => $maximums,
            ];
        }

        return $datasets;
    }
}
