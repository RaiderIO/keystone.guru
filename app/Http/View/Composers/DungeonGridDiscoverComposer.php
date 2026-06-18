<?php

namespace App\Http\View\Composers;

use App\Models\AffixGroup\AffixGroup;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use Illuminate\View\View;

class DungeonGridDiscoverComposer
{
    public function __construct(
        private readonly AffixGroupEaseTierServiceInterface $affixGroupEaseTierService,
    ) {
    }

    public function compose(View $view): void
    {
        /** @var AffixGroup|null $currentAffixGroup */
        $currentAffixGroup = $view->getData()['currentAffixGroup'];
        /** @var AffixGroup|null $nextAffixGroup */
        $nextAffixGroup = $view->getData()['nextAffixGroup'];
        $view->with('tiers', $this->affixGroupEaseTierService->getTiersByAffixGroups(collect([
            $currentAffixGroup,
            $nextAffixGroup,
        ])));
    }
}
