<?php

use App\Models\DungeonRoute\DungeonRouteCollection;

/**
 * A compact collection tile for the creator podium.
 *
 * Deliberately does not render the collection's routes, nor a route count: the count would include
 * routes the viewer may not see, which is a hint this podium has no business giving. The
 * collection page itself filters them per viewer.
 *
 * @var DungeonRouteCollection $dungeonRouteCollection
 */
?>
<a href="{{ route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}"
   class="collection_card card h-100 text-decoration-none">
    <div class="card-body">
        <div class="collection_card_name">
            {{ $dungeonRouteCollection->name }}
        </div>

        @if($dungeonRouteCollection->dungeonRouteCollectionCategory !== null)
            <span class="badge bg-info">
                {{ $dungeonRouteCollection->dungeonRouteCollectionCategory->getTranslatedName() }}
            </span>
        @endif

        @if(!empty($dungeonRouteCollection->description))
            <p class="collection_card_description text-body-secondary small mt-2 mb-0">
                {{ $dungeonRouteCollection->description }}
            </p>
        @endif
    </div>
</a>
