<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * The featured-creators rail that opens the reworked per-dungeon route page.
 *
 * It sits above the hero band, and it cannot sit between the hero band and the leaderboard even
 * though that looks like the natural middle ground: with no Raider.IO weekly routes the hero band is
 * the top three of the same popular query and the leaderboard's startRank continues from it, so a
 * section wedged in there splits ranks 1-3 from 4-onwards.
 *
 * Opening the page is what makes it a rail rather than a section. A six-across grid of tiles here
 * would cold-open the page on creators and push the routes the visitor came for below the fold; one
 * row of horizontal entries costs about 100px and reads as a caption above the hero, not as a
 * section competing with it.
 *
 * Two earlier passes read as a list of tags rather than as people, and neither cause was size: the
 * label sat inline ahead of the entries ("label: item item item" is tag-list grammar), and each
 * entry carried one short string. Hence the heading on its own line, and the published route count
 * under each name - which is also the evidence that makes a featured creator worth clicking.
 *
 * The entries are deliberately not creator.card: that tile is the directory's skin, and its fixed
 * vertical composition cannot compress to a rail. See creator-featured.css.
 *
 * featuredCreators is supplied by FeaturedCreatorsComposer rather than by the controller, because
 * several controller methods render the pages this partial is included from. It is deliberately not
 * defaulted here: the composer is bound to this exact view, so an undefined variable means the
 * binding is gone - which should be a loud error, not a section that silently stops rendering.
 *
 * @var Collection<int, User> $featuredCreators
 */
?>
@if($featuredCreators->isNotEmpty())
    {{-- A named landmark rather than a heading: dropping the heading row left seven sibling links
         with nothing to announce or navigate to them by. What this is, is navigation. --}}
    <nav class="discover_creator_rail mt-4" aria-label="{{ __('view_creator.featured.title') }}">
        {{-- On its own line, not inline ahead of the entries: "label: item item item" on one line is
             the grammar of a tag list, and the rail read as one. The link doubles as the label. --}}
        <div class="discover_creator_rail_heading">
            <a href="{{ route('creators.index') }}"
               class="discover_creator_rail_label"
               title="{{ __('view_creator.featured.see_all') }}">
                {{ __('view_creator.featured.title') }}
                <i class="fas fa-angle-right" aria-hidden="true"></i>
            </a>
        </div>

        <div class="discover_creator_rail_entries">
            @foreach($featuredCreators as $creator)
                <?php
                // Set by the withCount() in CreatorDirectoryService; fall back rather than lazy-count per entry
                $publishedRouteCount = $creator->published_route_count ?? 0;
                ?>
                <a href="{{ route('profile.view', ['user' => $creator]) }}"
                   class="discover_creator_entry"
                   {{-- Carries the name too, not just the count: the name clips to an ellipsis at
                        rail width (always, under the phone cap) and the tooltip is its reveal path --}}
                   title="{{ __('view_creator.featured.entry_title', [
                       'name'   => $creator->name,
                       'routes' => trans_choice('view_creator.card.route_count', $publishedRouteCount, ['count' => $publishedRouteCount]),
                   ]) }}">
                    @if($creator->iconfile !== null)
                        {{-- Decorative: the name is already the link's own text, right beside it --}}
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
                        {{-- The count is the evidence that makes a featured creator worth a click;
                             hiding it in the tooltip is what made an entry read as a bare tag --}}
                        <span class="discover_creator_count text-body-secondary">
                            {{ trans_choice('view_creator.card.route_count', $publishedRouteCount, ['count' => $publishedRouteCount]) }}
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </nav>
@endif
