<?php

namespace App\Http\View\Composers;

use App\Models\Dungeon;
use App\Service\Creator\CreatorDirectoryServiceInterface;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Binds the featured creators onto the partial itself rather than onto the page including it.
 *
 * The rail is a self-contained section that any of the discover surfaces may pick up, and the
 * controllers behind them already assemble a lot. Composing the partial means adding it to another
 * page is a single @include that can never silently render empty because someone forgot to pass the
 * data - the same reason DiscoverAffixGroupComposer exists. The including page passes the dungeon
 * the rail is for.
 */
readonly class FeaturedCreatorsComposer implements ViewComposerInterface
{
    public function __construct(
        private CreatorDirectoryServiceInterface $creatorDirectoryService,
    ) {
    }

    public function compose(View $view): void
    {
        $dungeon = $view->getData()['dungeon'] ?? null;

        if (!$dungeon instanceof Dungeon) {
            throw new InvalidArgumentException('creator.featured must be included with the dungeon it features creators for');
        }

        $view->with('featuredCreators', $this->creatorDirectoryService->getFeaturedCreators($dungeon));
    }
}
