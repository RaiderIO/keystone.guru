<?php

use App\Models\Dungeon;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * The featured-creators rail that opens the per-dungeon route page. It sits above the hero band: between the hero
 * band and the leaderboard it would split ranks 1-3 from the leaderboard's startRank onwards.
 *
 * featuredCreators comes from FeaturedCreatorsComposer, bound to this exact view, so it is deliberately not
 * defaulted: an undefined variable means the binding is gone.
 *
 * @var Dungeon               $dungeon
 * @var Collection<int, User> $featuredCreators
 */
?>
@if($featuredCreators->isNotEmpty())
    <?php $featuredTitle = __('view_creator.featured.title_dungeon', ['dungeon' => __($dungeon->name)]); ?>
    <nav class="discover_creator_rail mt-4" aria-label="{{ $featuredTitle }}">
        <div class="discover_creator_rail_heading">
            <a href="{{ route('creators.index') }}"
               class="discover_creator_rail_label"
               title="{{ __('view_creator.featured.see_all') }}">
                {{ $featuredTitle }}
                <i class="fas fa-angle-right" aria-hidden="true"></i>
            </a>
        </div>

        <div class="discover_creator_rail_entries">
            @foreach($featuredCreators as $creator)
                <?php
                $dungeonRouteCount = (int)$creator->dungeon_route_count;
                ?>
                <a href="{{ route('profile.view', ['user' => $creator]) }}"
                   class="discover_creator_entry"
                   {{-- A long name clips to an ellipsis; the title is its only reveal path --}}
                   title="{{ __('view_creator.featured.entry_title', [
                       'name'   => $creator->name,
                       'routes' => trans_choice('view_creator.featured.dungeon_route_count', $dungeonRouteCount, [
                           'count'   => $dungeonRouteCount,
                           'dungeon' => __($dungeon->name),
                       ]),
                   ]) }}">
                    @if($creator->iconfile !== null)
                        <img src="{{ $creator->iconfile->getURL() }}"
                             alt=""
                             class="discover_creator_avatar"/>
                    @else
                        <span class="discover_creator_initials" aria-hidden="true">
                            {{ $creator->initials }}
                        </span>
                    @endif

                    <span class="discover_creator_text">
                        <span class="discover_creator_name">
                            {{ $creator->name }}
                        </span>
                        <span class="discover_creator_count text-body-secondary">
                            {{ trans_choice('view_creator.featured.route_count', $dungeonRouteCount, ['count' => $dungeonRouteCount]) }}
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </nav>
@endif
