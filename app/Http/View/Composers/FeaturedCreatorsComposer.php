<?php

namespace App\Http\View\Composers;

use App\Service\Creator\CreatorDirectoryServiceInterface;
use Illuminate\View\View;

/**
 * Binds the featured creators onto the partial itself rather than onto the discover landing page.
 *
 * Three different controller methods render dungeonroute.discover.discover, so passing the data
 * from each one would have to be repeated three times and would silently break the day a fourth is
 * added. Composing the partial keeps the landing page's change to a single @include.
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
