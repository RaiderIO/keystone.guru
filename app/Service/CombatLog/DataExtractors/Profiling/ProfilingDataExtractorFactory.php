<?php

namespace App\Service\CombatLog\DataExtractors\Profiling;

use App\Service\CombatLog\DataExtractors\DataExtractorFactoryInterface;
use App\Service\CombatLog\DataExtractors\DataExtractorInterface;
use Illuminate\Support\Collection;

/**
 * Wraps every extractor the decorated factory produces in a {@see ProfilingDataExtractor}. Only
 * combatlog:benchmark --profile installs this factory (via Container::instance()); nothing in the production
 * object graph references it.
 */
class ProfilingDataExtractorFactory implements DataExtractorFactoryInterface
{
    /** @var Collection<int, ProfilingDataExtractor> Every extractor created through this factory - the benchmark creates one extraction service (and thus one extractor set) per corpus run, so aggregation must span all of them */
    private Collection $createdExtractors;

    public function __construct(
        private readonly DataExtractorFactoryInterface $dataExtractorFactory,
    ) {
        $this->createdExtractors = collect();
    }

    public function createExtractors(): Collection
    {
        $profilingExtractors = $this->dataExtractorFactory->createExtractors()
            ->map(static fn(DataExtractorInterface $dataExtractor) => new ProfilingDataExtractor($dataExtractor));

        $this->createdExtractors = $this->createdExtractors->merge($profilingExtractors);

        /** @var Collection<int, DataExtractorInterface> $extractors */
        $extractors = $profilingExtractors;

        return $extractors;
    }

    /**
     * @return Collection<int, ProfilingDataExtractor>
     */
    public function getCreatedExtractors(): Collection
    {
        return $this->createdExtractors;
    }
}
