<?php

namespace App\Service\CombatLog\DataExtractors\Profiling;

use App\Logic\CombatLog\BaseEvent;
use App\Service\CombatLog\DataExtractors\DataExtractorInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;

/**
 * Decorator that accumulates the time spent in each lifecycle call of the wrapped extractor. Only
 * {@see ProfilingDataExtractorFactory} installs these - the production object graph never contains them, so
 * the cost of profiling is zero (not "one branch per line") when it is off.
 *
 * hrtime() is used over microtime()/Stopwatch on purpose: it is monotonic and nanosecond-resolution, and
 * Stopwatch consults config('debugbar.enabled') on every start/pause which would distort per-line timings.
 */
class ProfilingDataExtractor implements DataExtractorInterface
{
    private int $beforeExtractNanos = 0;

    private int $beforeExtractCalls = 0;

    private int $extractDataNanos = 0;

    private int $extractDataCalls = 0;

    private int $afterExtractNanos = 0;

    private int $afterExtractCalls = 0;

    public function __construct(
        private readonly DataExtractorInterface $dataExtractor,
    ) {
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $start = hrtime(true);

        try {
            $this->dataExtractor->beforeExtract($result, $combatLogFilePath);
        } finally {
            $this->beforeExtractNanos += hrtime(true) - $start;
            $this->beforeExtractCalls++;
        }
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
        $start = hrtime(true);

        try {
            $this->dataExtractor->extractData($result, $currentDungeon, $parsedEvent);
        } finally {
            $this->extractDataNanos += hrtime(true) - $start;
            $this->extractDataCalls++;
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $start = hrtime(true);

        try {
            $this->dataExtractor->afterExtract($result, $combatLogFilePath);
        } finally {
            $this->afterExtractNanos += hrtime(true) - $start;
            $this->afterExtractCalls++;
        }
    }

    public function getDataExtractor(): DataExtractorInterface
    {
        return $this->dataExtractor;
    }

    public function getTotalNanos(): int
    {
        return $this->beforeExtractNanos + $this->extractDataNanos + $this->afterExtractNanos;
    }

    public function getTotalCalls(): int
    {
        return $this->beforeExtractCalls + $this->extractDataCalls + $this->afterExtractCalls;
    }

    /**
     * @return array<string, array{nanos: int, calls: int}>
     */
    public function getTimings(): array
    {
        return [
            'beforeExtract' => ['nanos' => $this->beforeExtractNanos, 'calls' => $this->beforeExtractCalls],
            'extractData'   => ['nanos' => $this->extractDataNanos, 'calls' => $this->extractDataCalls],
            'afterExtract'  => ['nanos' => $this->afterExtractNanos, 'calls' => $this->afterExtractCalls],
        ];
    }
}
