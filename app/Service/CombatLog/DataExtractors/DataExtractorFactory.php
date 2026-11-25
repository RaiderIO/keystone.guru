<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Repositories\Interfaces\CombatLog\CombatLogEncounterObservationRepositoryInterface;
use App\Repositories\Interfaces\CombatLog\CombatLogWalkGraphContributionRepositoryInterface;
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
        private readonly SpellRepositorySwooleInterface                    $spellRepository,
        private readonly CombatLogWalkGraphContributionRepositoryInterface $combatLogWalkGraphContributionRepository,
        private readonly CombatLogEncounterObservationRepositoryInterface  $combatLogEncounterObservationRepository,
    ) {
    }

    public function createExtractors(): Collection
    {
        $spellPropertyObservationBuffer = new SpellPropertyObservationBuffer();

        /** @var Collection<int, DataExtractorInterface> $extractors */
        $extractors = collect([
            new CreateMissingNpcDataExtractor(),
            new SpellDataExtractor($this->spellRepository, $spellPropertyObservationBuffer),
            new NpcCharacteristicDataExtractor($this->spellRepository),
            new SpellCounterDataExtractor($this->spellRepository, $spellPropertyObservationBuffer),
            new ImmunityBypassDataExtractor($spellPropertyObservationBuffer),
            new WalkGraphDataExtractor($this->combatLogWalkGraphContributionRepository),
            new EncounterMechanicDataExtractor($this->combatLogEncounterObservationRepository),
            new SpellPropertyObservationFlushDataExtractor($spellPropertyObservationBuffer),
        ]);

        return $extractors;
    }
}
