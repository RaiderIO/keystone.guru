<?php

namespace App\Http\View\Composers;

use App\Service\Creator\CreatorDirectoryServiceInterface;
use Illuminate\View\View;

/**
 * Binds the featured creators onto the partial itself rather than onto the page including it.
 *
 * The strip is a self-contained section that any of the discover surfaces may pick up, and the
 * controllers behind them already assemble a lot. Composing the partial means adding it to another
 * page is a single @include that can never silently render empty because someone forgot to pass the
 * data - the same reason DiscoverSearchComposer and DiscoverAffixGroupComposer exist.
 */
readonly class FeaturedCreatorsComposer implements ViewComposerInterface
{
    public function __construct(
        private CreatorDirectoryServiceInterface $creatorDirectoryService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('featuredCreators', $this->creatorDirectoryService->getFeaturedCreators());
    }
}
