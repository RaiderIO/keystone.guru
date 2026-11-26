<?php

namespace App\Console\Commands\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Logging\StructuredLogging;
use App\Service\SimulationCraft\Dtos\TravelDelayCalibrationSummary;
use App\Service\SimulationCraft\Dtos\TravelDelayTransition;
use App\Service\SimulationCraft\TravelDelayCalibrationServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

class CalibrateTravelDelay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:calibratetraveldelay
        {filePath : A combat log route .json file, or a directory of them}
        {--rangedPullCompensation=35 : Yards of travel the ranged pull compensation takes off every pull}
        {--noMounts : Calculate the delays without mounts}
        {--walkSpeed= : Override the walking speed in yards per second}
        {--driftAfterTrash= : Override the share of the path covered while still fighting a trash pull}
        {--driftAfterBoss= : Override the share of the path covered while still fighting a boss}
        {--overhead= : Override the seconds between pulls that are not travel}
        {--csv= : Write every transition to this .csv file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compares the SimulationCraft travel delay between pulls with the time real runs took, from combat log route .json files.';

    public function handle(TravelDelayCalibrationServiceInterface $travelDelayCalibrationService): int
    {
        $filePath  = (string)$this->argument('filePath');
        $filePaths = is_dir($filePath) ? glob(sprintf('%s/*.json', rtrim($filePath, '/'))) : [$filePath];

        /** @var Collection<int, TravelDelayTransition> $transitions */
        $transitions = collect();
        /** @var list<string> $transitionFileNames The file each transition came from, by the transition's position */
        $transitionFileNames = [];
        $failed              = 0;

        StructuredLogging::disable();

        $this->applyConfigOverrides();

        try {
            foreach ($filePaths as $jsonFilePath) {
                try {
                    $runTransitions = $travelDelayCalibrationService->measureCombatLogRoute(
                        CombatLogRouteRequestDto::createFromArray(json_decode(file_get_contents($jsonFilePath), true)),
                        (int)$this->option('rangedPullCompensation'),
                        !$this->option('noMounts'),
                    );
                } catch (Throwable $throwable) {
                    $this->error(sprintf('- %s: %s', basename($jsonFilePath), $throwable->getMessage()));
                    $failed++;

                    continue;
                }

                $this->info(sprintf('- %s: %d transitions', basename($jsonFilePath), $runTransitions->count()));
                $transitions = $transitions->concat($runTransitions);
                array_push($transitionFileNames, ...array_fill(0, $runTransitions->count(), basename($jsonFilePath)));
            }
        } finally {
            StructuredLogging::enable();
        }

        $csvFilePath = $this->option('csv');
        if (is_string($csvFilePath) && $csvFilePath !== '') {
            $this->writeCsv($csvFilePath, $transitions->values(), $transitionFileNames);
        }

        $rows = $transitions
            ->groupBy(static fn(TravelDelayTransition $transition): string => $transition->dungeonKey)
            ->sortKeys()
            ->map(fn(Collection $dungeonTransitions, string $dungeonKey): array => $this->getSummaryRow(
                $dungeonKey,
                $travelDelayCalibrationService->summarize($dungeonTransitions),
            ))
            ->values()
            ->push($this->getSummaryRow('all', $travelDelayCalibrationService->summarize($transitions)));

        $this->table(
            ['Dungeon', 'Used', 'Excluded', 'Median predicted', 'Median observed', 'Total predicted', 'Total observed', 'Median ratio', 'Fit slope', 'Fit intercept'],
            $rows->all(),
        );

        return $failed === 0 ? 0 : 1;
    }

    /**
     * @return list<string|int>
     */
    private function getSummaryRow(string $label, TravelDelayCalibrationSummary $summary): array
    {
        $format = static fn(?float $value): string => $value === null ? '-' : sprintf('%.2f', $value);

        return [
            $label,
            $summary->includedCount,
            $summary->excludedCount,
            $format($summary->medianPredictedSeconds),
            $format($summary->medianObservedSeconds),
            $format($summary->totalPredictedSeconds),
            $format($summary->totalObservedSeconds),
            $format($summary->medianRatio),
            $format($summary->slope),
            $format($summary->intercept),
        ];
    }

    /**
     * @param Collection<int, TravelDelayTransition> $transitions
     * @param list<string>                           $transitionFileNames
     */
    private function writeCsv(string $csvFilePath, Collection $transitions, array $transitionFileNames): void
    {
        $handle = fopen($csvFilePath, 'w');
        fputcsv(
            $handle,
            ['dungeon', 'pull', 'predicted_seconds', 'observed_seconds', 'exclusion', 'predicted_yards', 'previous_pull_ended_at', 'pull_started_at', 'file'],
            escape: '',
        );
        foreach ($transitions as $index => $transition) {
            fputcsv($handle, [
                $transition->dungeonKey,
                $transition->pullIndex,
                $transition->predictedSeconds,
                sprintf('%.3f', $transition->observedSeconds),
                $transition->exclusion->value ?? '',
                sprintf('%.1f', $transition->predictedYards),
                $transition->previousPullEndedAt?->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT) ?? '',
                $transition->pullStartedAt?->format(CombatLogRouteRequestDto::DATE_TIME_FORMAT) ?? '',
                $transitionFileNames[$index] ?? '',
            ], escape: '');
        }
        fclose($handle);

        $this->info(sprintf('Wrote %d transitions to %s', $transitions->count(), $csvFilePath));
    }

    private function applyConfigOverrides(): void
    {
        $overrides = [
            'walkSpeed'       => 'keystoneguru.character.default_movement_speed_yards_second',
            'driftAfterTrash' => 'keystoneguru.simulationcraft.travel.drift_fraction_after_trash',
            'driftAfterBoss'  => 'keystoneguru.simulationcraft.travel.drift_fraction_after_boss',
            'overhead'        => 'keystoneguru.simulationcraft.travel.overhead_seconds',
        ];

        foreach ($overrides as $option => $configKey) {
            if ($this->option($option) !== null) {
                config([$configKey => (float)$this->option($option)]);
            }
        }
    }
}
