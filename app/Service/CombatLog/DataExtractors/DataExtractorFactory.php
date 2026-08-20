<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Repositories\Swoole\Interfaces\SpellRepositorySwooleInterface;
use Illuminate\Support\Collection;

class DataExtractorFactory implements DataExtractorFactoryInterface
{
    /**
     * The Swoole spell repository is process-persistent (bound with app()->instance() in
     * OctaneServiceProvider), so its memoized spell catalogs survive across jobs in a long-lived queue
     * worker - that is what makes extractor construction cheap after the first job (#4058).
     */
    public function __construct(
        private readonly SpellRepositorySwooleInterface $spellRepository,
    ) {
    }

    public function createExtractors(): Collection
    {
        /** @var Collection<int, DataExtractorInterface> $extractors */
        $extractors = collect([
            new CreateMissingNpcDataExtractor(),
            new SpellDataExtractor($this->spellRepository),
            new NpcCharacteristicDataExtractor($this->spellRepository),
            new SpellCounterDataExtractor(),
            new ImmunityBypassDataExtractor(),
        ]);

        return $extractors;
    }
}
