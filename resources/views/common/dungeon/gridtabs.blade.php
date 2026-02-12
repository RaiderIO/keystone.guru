<?php

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var GameVersion           $currentUserGameVersion
 * @var GameVersion|null      $gameVersion
 * @var Season|null           $nextSeason
 * @var Season                $currentSeason
 * @var Collection<Expansion> $activeExpansions
 * @var string                $id
 * @var string                $tabsId
 * @var bool                  $selectable
 * @var callable|null         $subtextFn
 * @var callable|null         $filterFn
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
$nextSeasonDungeons    = $nextSeason?->dungeons->filter($filterFn)->values() ?? collect();
$currentSeasonDungeons = $currentSeason?->dungeons->filter($filterFn)->values() ?? collect();

$selectedSeasonId = null;
if ($gameVersion->has_seasons) {
    if ($nextSeason !== null && $nextSeasonDungeons->isNotEmpty()) {
        $selectedSeasonId = $nextSeason->id;
    } else if ($currentSeasonDungeons->isNotEmpty()) {
        $selectedSeasonId = $currentSeason->id;
    }
}

$showFullExpansionName = $nextSeason !== null && $nextSeason->expansion_id !== $currentSeason->expansion_id;
?>
<div id="{{ $id }}">
    <ul id="{{ $tabsId }}" class="nav nav-tabs" role="tablist">
        @if($gameVersion->has_seasons)
            @if($nextSeason !== null && $nextSeasonDungeons->isNotEmpty())
                <li class="nav-item">
                    <a id="season-{{ $nextSeason->id }}-grid-tab"
                       class="nav-link active"
                       href="#season-{{ $nextSeason->id }}-grid-content"
                       role="tab"
                       aria-controls="season-{{ $nextSeason->id }}-grid-content"
                       aria-selected="{{ $selectedSeasonId === $nextSeason->id ? 'true' : 'false' }}"
                       data-toggle="tab"
                       data-season="{{ $nextSeason->id }}"
                    >{{ $showFullExpansionName ? $nextSeason->name_long : $nextSeason->name }}</a>
                </li>
            @endif
            @if($currentSeasonDungeons->isNotEmpty())
                <li class="nav-item">
                    <a id="season-{{ $currentSeason->id }}-grid-tab"
                       class="nav-link {{ $nextSeason === null || $nextSeasonDungeons->isEmpty() ? 'active' : '' }}"
                       href="#season-{{ $currentSeason->id }}-grid-content"
                       role="tab"
                       aria-controls="season-{{ $currentSeason->id }}-grid-content"
                       aria-selected="{{ $selectedSeasonId === $currentSeason->id ? 'true' : 'false' }}"
                       data-toggle="tab"
                       data-season="{{ $currentSeason->id }}"
                    >{{ $currentSeason->name }}</a>
                </li>
            @endif
        @endif
        <?php
        $index = 0; ?>
        @foreach($activeExpansions as $expansion)
                <?php /** @var Expansion $expansion */ ?>
            @if($expansion->hasDungeonForGameVersion($gameVersion, $filterFn))
                @php($active = $selectedSeasonId === null && $index === 0)
                <li class="nav-item">
                    <a id="{{ $expansion->shortname }}-grid-tab"
                       class="nav-link {{ $active ? 'active' : '' }}"
                       href="#{{ $expansion->shortname }}-grid-content"
                       role="tab"
                       aria-controls="{{ $expansion->shortname }}-grid-content"
                       aria-selected="{{ $active ? 'true' : 'false' }}"
                       data-toggle="tab"
                       data-expansion="{{ $expansion->shortname }}"
                    >{{ __($expansion->name) }}</a>
                </li>
                @php($index++)
            @endif
            @if($expansion->hasRaidForGameVersion($gameVersion, $filterFn))
                @php($active = $selectedSeasonId === null && $index === 0)
                <li class="nav-item">
                    <a id="{{ $expansion->shortname }}-raid-grid-tab"
                       class="nav-link {{ $active ? 'active' : '' }}"
                       href="#{{ $expansion->shortname }}-raid-grid-content"
                       role="tab"
                       aria-controls="{{ $expansion->shortname }}-raid-grid-content"
                       aria-selected="{{ $active ? 'true' : 'false' }}"
                       data-toggle="tab"
                       data-expansion="{{ $expansion->shortname }}-raid"
                    >{{ __($expansion->name) }} ({{ __('view_common.dungeon.gridtabs.raid') }}) </a>
                </li>
                @php($index++)
            @endif
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
        <?php
        $index = 0; ?>
        @foreach($activeExpansions as $expansion)
                <?php /** @var Expansion $expansion */ ?>

            @if($expansion->hasDungeonForGameVersion($gameVersion, $filterFn))
                <div id="{{ $expansion->shortname }}-grid-content"
                     class="tab-pane fade show {{ $selectedSeasonId === null && $index === 0 ? 'active' : '' }}"
                     role="tabpanel"
                     aria-labelledby="{{ $expansion->shortname }}-grid-content">
                    @php($dungeons = $expansion->dungeons()->active()->forGameVersion($gameVersion)->get()->filter($filterFn)->values())

                    @include('common.dungeon.grid', [
                        'test' => $expansion->shortname === 'bfa',
                        'expansion' => $expansion,
                        'dungeons' => $dungeons,
                        'names' => true,
                        'selectable' => true,
                        'route' => $route,
                        'links' => $route === null ? collect() : $dungeons->map($linkMapFn),
                        'subtextFn' => $subtextFn,
                    ])
                </div>
                @php($index++)
            @endif

            @if($expansion->hasRaidForGameVersion($gameVersion, $filterFn))
                <div id="{{ $expansion->shortname }}-raid-grid-content"
                     class="tab-pane fade show {{ $selectedSeasonId === null && $index === 0 ? 'active' : '' }}"
                     role="tabpanel"
                     aria-labelledby="{{ $expansion->shortname }}-raid-grid-content">
                    @php($raids = $expansion->raids()->active()->forGameVersion($gameVersion)->get()->filter($filterFn)->values())
                    @include('common.dungeon.grid', [
                        'expansion' => $expansion,
                        'dungeons' => $raids,
                        'names' => true,
                        'selectable' => true,
                        'route' => $route,
                        'links' => $route === null ? collect() : $raids->map($linkMapFn),
                        'subtextFn' => $subtextFn,
                    ])
                </div>
                @php($index++)
            @endif
        @endforeach
    </div>
</div>
