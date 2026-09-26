<?php

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var GameVersion                $currentUserGameVersion
 * @var GameVersion|null           $gameVersion
 * @var Season|null                $nextSeason
 * @var Season                     $currentSeason
 * @var int|null                   $requestedSeasonId
 * @var Collection<int, Expansion> $activeExpansions
 * @var string                     $id
 * @var string                     $tabsId
 * @var bool                       $selectable
 * @var callable|null              $subtextFn
 * @var callable|null              $filterFn
 */

$gameVersion           ??= $currentUserGameVersion;
$selectable            ??= true;
$route                 ??= null;
$routeParams           ??= [];
$linkMapFn             = static fn(Dungeon $dungeon) => [
    'dungeon' => $dungeon->key,
    'link'    => route($route, array_merge($routeParams, ['dungeon' => $dungeon])),
];
$subtextFn             ??= null;
$filterFn              ??= fn(Dungeon $dungeon) => true;
$activeFilterFn        = fn(Dungeon $dungeon) => $dungeon->active && $filterFn($dungeon);
$nextSeasonDungeons    = $nextSeason?->dungeons->filter($activeFilterFn)->values() ?? collect();
$currentSeasonDungeons = $currentSeason->dungeons->filter($activeFilterFn)->values();

// Open on the current season - the next season is seeded well before it starts, and until then it should
// not take over the selection (#3761). It is opened only when it was explicitly asked for, or when the
// current season has nothing to show for this game version.
$selectedSeasonId = null;
if ($gameVersion->has_seasons) {
    if ($nextSeason !== null && $nextSeasonDungeons->isNotEmpty() && $requestedSeasonId === $nextSeason->id) {
        $selectedSeasonId = $nextSeason->id;
    } else if ($currentSeasonDungeons->isNotEmpty()) {
        $selectedSeasonId = $currentSeason->id;
    } else if ($nextSeason !== null && $nextSeasonDungeons->isNotEmpty()) {
        $selectedSeasonId = $nextSeason->id;
    }
}

$showFullExpansionName = $nextSeason !== null && $nextSeason->expansion_id !== $currentSeason->expansion_id;

/**
 * One tab per expansion's dungeons and one per its raids, only for those with something to show.
 *
 * @var Collection<int, array{key: string, name: string, dungeons: Collection<int, Dungeon>}> $expansionTabs
 */
$expansionTabs = collect();
foreach ($activeExpansions as $expansion) {
    $expansionDungeons = $expansion->dungeons()->active()->forGameVersion($gameVersion)->get()->filter($filterFn)->values();
    if ($expansionDungeons->isNotEmpty()) {
        $expansionTabs->push([
            'key'      => $expansion->shortname,
            'name'     => __($expansion->name),
            'dungeons' => $expansionDungeons,
        ]);
    }

    $expansionRaids = $expansion->raids()->active()->forGameVersion($gameVersion)->get()->filter($filterFn)->values();
    if ($expansionRaids->isNotEmpty()) {
        $expansionTabs->push([
            'key'      => sprintf('%s-raid', $expansion->shortname),
            'name'     => sprintf('%s (%s)', __($expansion->name), __('view_common.dungeon.gridtabs.raid')),
            'dungeons' => $expansionRaids,
        ]);
    }
}
?>
<div id="{{ $id }}">
    <ul id="{{ $tabsId }}" class="nav nav-tabs" role="tablist">
        @if($gameVersion->has_seasons)
            @if($nextSeason !== null && $nextSeasonDungeons->isNotEmpty())
                <li class="nav-item">
                    <a id="season-{{ $nextSeason->id }}-grid-tab"
                       class="nav-link {{ $selectedSeasonId === $nextSeason->id ? 'active' : '' }}"
                       href="#season-{{ $nextSeason->id }}-grid-content"
                       role="tab"
                       aria-controls="season-{{ $nextSeason->id }}-grid-content"
                       aria-selected="{{ $selectedSeasonId === $nextSeason->id ? 'true' : 'false' }}"
                       data-bs-toggle="tab"
                       data-season="{{ $nextSeason->id }}"
                    >{{ $showFullExpansionName ? $nextSeason->name_long : $nextSeason->name }}</a>
                </li>
            @endif
            @if($currentSeasonDungeons->isNotEmpty())
                <li class="nav-item">
                    <a id="season-{{ $currentSeason->id }}-grid-tab"
                       class="nav-link {{ $selectedSeasonId === $currentSeason->id ? 'active' : '' }}"
                       href="#season-{{ $currentSeason->id }}-grid-content"
                       role="tab"
                       aria-controls="season-{{ $currentSeason->id }}-grid-content"
                       aria-selected="{{ $selectedSeasonId === $currentSeason->id ? 'true' : 'false' }}"
                       data-bs-toggle="tab"
                       data-season="{{ $currentSeason->id }}"
                    >{{ $currentSeason->name }}</a>
                </li>
            @endif
        @endif
        @foreach($expansionTabs as $index => $expansionTab)
            @php($active = $selectedSeasonId === null && $index === 0)
            <li class="nav-item">
                <a id="{{ $expansionTab['key'] }}-grid-tab"
                   class="nav-link {{ $active ? 'active' : '' }}"
                   href="#{{ $expansionTab['key'] }}-grid-content"
                   role="tab"
                   aria-controls="{{ $expansionTab['key'] }}-grid-content"
                   aria-selected="{{ $active ? 'true' : 'false' }}"
                   data-bs-toggle="tab"
                   data-expansion="{{ $expansionTab['key'] }}"
                >{{ $expansionTab['name'] }}</a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @if($gameVersion->has_seasons)
            @if($nextSeason !== null && $nextSeasonDungeons->isNotEmpty())
                <div id="season-{{ $nextSeason->id }}-grid-content"
                     class="tab-pane fade show {{ $selectedSeasonId === $nextSeason->id ? 'active' : '' }}"
                     role="tabpanel"
                     aria-labelledby="season-{{ $nextSeason->id }}-grid-content">
                    @include('common.dungeon.grid', [
                        'dungeons' => $nextSeasonDungeons,
                        'names' => true,
                        'selectable' => true,
                        'route' => $route,
                        'links' => $route === null ? collect() : $nextSeasonDungeons->map($linkMapFn),
                        'subtextFn' => $subtextFn,
                    ])
                </div>
            @endif
            @if($currentSeasonDungeons->isNotEmpty())
                <div id="season-{{ $currentSeason->id }}-grid-content"
                     class="tab-pane fade show {{ $selectedSeasonId === $currentSeason->id ? 'active' : '' }}"
                     role="tabpanel"
                     aria-labelledby="season-{{ $currentSeason->id }}-grid-content">
                    @include('common.dungeon.grid', [
                        'dungeons' => $currentSeasonDungeons,
                        'names' => true,
                        'selectable' => true,
                        'route' => $route,
                        'links' => $route === null ? collect() : $currentSeasonDungeons->map($linkMapFn),
                        'subtextFn' => $subtextFn,
                    ])
                </div>
            @endif
        @endif
        @foreach($expansionTabs as $index => $expansionTab)
            <div id="{{ $expansionTab['key'] }}-grid-content"
                 class="tab-pane fade show {{ $selectedSeasonId === null && $index === 0 ? 'active' : '' }}"
                 role="tabpanel"
                 aria-labelledby="{{ $expansionTab['key'] }}-grid-content">
                @include('common.dungeon.grid', [
                    'dungeons' => $expansionTab['dungeons'],
                    'names' => true,
                    'selectable' => true,
                    'route' => $route,
                    'links' => $route === null ? collect() : $expansionTab['dungeons']->map($linkMapFn),
                    'subtextFn' => $subtextFn,
                ])
            </div>
        @endforeach
    </div>
</div>
