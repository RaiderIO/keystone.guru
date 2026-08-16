<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * The featured-creators strip that closes the reworked per-dungeon route page.
 *
 * It sits below the leaderboard rather than above the routes on purpose: the person finding a route
 * outranks the person who built one, so creators are the hand-off once the routes have been
 * scanned, not the thing standing between the visitor and them.
 *
 * The entries are deliberately not creator.card - that tile is the directory's skin, and stacking a
 * grid of cards under the leaderboard would reintroduce the visual weight the page has just spent
 * two sections shedding. See creator-featured.css.
 *
 * featuredCreators is supplied by FeaturedCreatorsComposer rather than by the controller, because
 * several controller methods render the pages this partial is included from.
 *
 * @var Collection<int, User> $featuredCreators
 */

$featuredCreators ??= collect();
?>
@if($featuredCreators->isNotEmpty())
    <div class="row mt-5 align-items-center discover_section_header">
        <div class="col">
            <h5 class="mb-0 text-center">
                {{-- The directory link lives in the heading itself, matching the "Weekly routes" section above --}}
                <a href="{{ route('creators.index') }}" title="{{ __('view_creator.featured.see_all') }}">
                    {{ __('view_creator.featured.title') }}
                    <i class="fas fa-angle-right" aria-hidden="true"></i>
                </a>
            </h5>
        </div>
    </div>

    <div class="discover_creator_strip mt-2">
        <div class="row g-2 row-cols-2 row-cols-sm-3 row-cols-lg-6">
            @foreach($featuredCreators as $creator)
                <?php
                // Set by the withCount() in CreatorDirectoryService; fall back rather than lazy-count per entry
                $publishedRouteCount = $creator->published_route_count ?? 0;
                ?>
                <div class="col">
                    <a href="{{ route('profile.view', ['user' => $creator]) }}" class="discover_creator_entry">
                        @if($creator->iconfile !== null)
                            <img src="{{ $creator->iconfile->getURL() }}"
                                 alt="{{ $creator->name }}"
                                 class="discover_creator_avatar"/>
                        @else
                            <div class="discover_creator_initials" aria-hidden="true">
                                {{ $creator->initials }}
                            </div>
                        @endif

                        <div class="discover_creator_name" title="{{ $creator->name }}">
                            {{ $creator->name }}
                        </div>

                        <div class="text-body-secondary small">
                            {{ trans_choice('view_creator.card.route_count', $publishedRouteCount, ['count' => $publishedRouteCount]) }}
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif
