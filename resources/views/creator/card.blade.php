<?php

use App\Models\User;

/**
 * A single creator tile, as the creator directory renders it. The featured-creators strip on the
 * per-dungeon route page deliberately uses its own flatter markup instead - see
 * creator/featured.blade.php for why.
 *
 * @var User $creator
 */

// Set by the withCount() in CreatorDirectoryService; fall back rather than lazy-count per card
$publishedRouteCount = $creator->published_route_count ?? 0;
?>
<a href="{{ route('profile.view', ['user' => $creator]) }}" class="creator_card card h-100 text-decoration-none">
    <div class="card-body text-center">
        @if($creator->iconfile !== null)
            <img src="{{ $creator->iconfile->getURL() }}"
                 alt="{{ $creator->name }}"
                 class="creator_card_avatar mb-2"/>
        @else
            <div class="creator_card_initials bg-secondary text-white mb-2" aria-hidden="true">
                {{ $creator->initials }}
            </div>
        @endif

        <div class="creator_card_name">
            {{ $creator->name }}
        </div>

        <div class="text-body-secondary small">
            {{ trans_choice('view_creator.card.route_count', $publishedRouteCount, ['count' => $publishedRouteCount]) }}
        </div>
    </div>
</a>
