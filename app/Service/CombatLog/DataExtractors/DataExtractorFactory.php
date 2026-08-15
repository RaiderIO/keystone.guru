<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use Illuminate\Support\Collection;

class DataExtractorFactory implements DataExtractorFactoryInterface
{
    public function __construct(
        private readonly FloorRepositoryInterface $floorRepository,
        private readonly SpellRepositoryInterface $spellRepository,
    ) {
    }

    public function createExtractors(): Collection
    {
        /** @var Collection<int, DataExtractorInterface> $extractors */
        $extractors = collect([
            new CreateMissingNpcDataExtractor(),
            new NpcUpdateDataExtractor(),
            new FloorDataExtractor($this->floorRepository),
            new SpellDataExtractor(),
            new NpcCharacteristicDataExtractor($this->spellRepository),
            new SpellCounterDataExtractor(),
            new ImmunityBypassDataExtractor(),
        ]);

        return $extractors;
    }
}
