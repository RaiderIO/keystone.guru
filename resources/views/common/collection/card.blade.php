<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use Illuminate\Support\Collection;

/**
 * A collection tile for the creator podium, built like the route poster card: the covers of its first routes are
 * the background, the name and what it covers sit on the scrim.
 *
 * Every count and cover comes from the routes the viewer may see, never from the collection as stored - the stored
 * count would include unpublished routes, a hint this podium has no business giving.
 *
 * @var DungeonRouteCollection        $dungeonRouteCollection
 * @var Collection<int, DungeonRoute> $dungeonRoutes          The collection's routes the viewer may see, in order.
 * @var int                           $coveredDungeonCount
 */

$coverUrls = $dungeonRoutes
    ->take(4)
    ->map(static fn(DungeonRoute $dungeonRoute): string => $dungeonRoute->has_thumbnail
        ? $dungeonRoute->thumbnails->first()->getURL()
        : $dungeonRoute->dungeon->getImage32Url())
    ->values();
?>
<a href="{{ route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}"
   class="collection_card">
    <span class="collection_card_mosaic" data-count="{{ $coverUrls->count() }}" aria-hidden="true">
        @foreach($coverUrls as $coverUrl)
            <span class="collection_card_cover" style="background-image: url('{{ $coverUrl }}')"></span>
        @endforeach
    </span>

    <span class="collection_card_scrim">
        <span class="collection_card_top">
            @if($dungeonRouteCollection->dungeonRouteCollectionCategory !== null)
                <span class="badge bg-info">
                    {{ $dungeonRouteCollection->dungeonRouteCollectionCategory->getTranslatedName() }}
                </span>
            @endif
            <span class="collection_card_count">
                <i class="fas fa-layer-group" aria-hidden="true"></i>
                {{ trans_choice('view_collection.view.route_count', $dungeonRoutes->count(), ['count' => $dungeonRoutes->count()]) }}
            </span>
        </span>

        <span class="collection_card_footer">
            <span class="collection_card_name">
                {{ $dungeonRouteCollection->name }}
            </span>
            <span class="collection_card_kind">
                @include('common.collection.kind', [
                    'dungeonRouteCollection' => $dungeonRouteCollection,
                    'coveredDungeonCount' => $coveredDungeonCount,
                ])
            </span>
            @if(!empty($dungeonRouteCollection->description))
                <span class="collection_card_description">
                    {{ $dungeonRouteCollection->description }}
                </span>
            @endif
        </span>
    </span>
</a>
