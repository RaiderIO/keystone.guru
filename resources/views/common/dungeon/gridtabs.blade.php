<?php

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var GameVersion           $currentUserGameVersion
 * @var Season|null           $nextSeason
 * @var Season                $currentSeason
 * @var Collection<Expansion> $activeExpansions
 * @var string                $id
 * @var string                $tabsId
 * @var bool                  $selectable
 * @var callable|null         $subtextFn
 * @var callable|null         $filterFn
 */

$selectedSeasonId      = $currentUserGameVersion->has_seasons ? ($nextSeason ?? $currentSeason)->id : null;
$selectable            ??= true;
$route                 ??= null;
$routeParams           ??= [];
$linkMapFn             = static fn(Dungeon $dungeon) => [
    'dungeon' => $dungeon->key,
    'link'    => route($route, array_merge($routeParams, ['dungeon' => $dungeon])),
];
$subtextFn             ??= null;
$filterFn              ??= fn(Dungeon $dungeon) => true;
$showFullExpansionName = $nextSeason !== null && $nextSeason->expansion_id !== $currentSeason->expansion_id;
?>
<div id="{{ $id }}">
    <ul id="{{ $tabsId }}" class="nav nav-tabs" role="tablist">
        @if($currentUserGameVersion->has_seasons)
            @if($nextSeason !== null)
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
            <li class="nav-item">
                <a id="season-{{ $currentSeason->id }}-grid-tab"
                   class="nav-link {{ $nextSeason === null ? 'active' : '' }}"
                   href="#season-{{ $currentSeason->id }}-grid-content"
                   role="tab"
                   aria-controls="season-{{ $currentSeason->id }}-grid-content"
                   aria-selected="{{ $selectedSeasonId === $currentSeason->id ? 'true' : 'false' }}"
                   data-toggle="tab"
                   data-season="{{ $currentSeason->id }}"
                >{{ $currentSeason->name }}</a>
            </li>
        @endif
        <?php
        $index = 0; ?>
        @foreach($activeExpansions as $expansion)
                <?php /** @var Expansion $expansion */ ?>
            @if($expansion->hasDungeonForGameVersion($currentUserGameVersion, $filterFn))
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
            @if($expansion->hasRaidForGameVersion($currentUserGameVersion, $filterFn))
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
        @if($currentUserGameVersion->has_seasons)
            @if($nextSeason !== null)
                <div id="season-{{ $nextSeason->id }}-grid-content"
                     class="tab-pane fade show {{ $selectedSeasonId === $nextSeason->id ? 'active' : '' }}"
                     role="tabpanel"
                     aria-labelledby="season-{{ $nextSeason->id }}-grid-content">
                    @php($dungeons = $nextSeason->dungeons->filter($filterFn)->values())
                    @include('common.dungeon.grid', [
                        'dungeons' => $dungeons,
                        'names' => true,
                        'selectable' => true,
                        'route' => $route,
                        'links' => $route === null ? collect() : $dungeons->map($linkMapFn),
                        'subtextFn' => $subtextFn,
                    ])
                </div>
            @endif
            <div id="season-{{ $currentSeason->id }}-grid-content"
                 class="tab-pane fade show {{ $selectedSeasonId === $currentSeason->id ? 'active' : '' }}"
                 role="tabpanel"
                 aria-labelledby="season-{{ $currentSeason->id }}-grid-content">
                @php($dungeons = $currentSeason->dungeons->filter($filterFn)->values())
                @include('common.dungeon.grid', [
                    'dungeons' => $dungeons,
                    'names' => true,
                    'selectable' => true,
                    'route' => $route,
                    'links' => $route === null ? collect() : $dungeons->map($linkMapFn),
                    'subtextFn' => $subtextFn,
                ])
            </div>
        @endif
        <?php
        $index = 0; ?>
        @foreach($activeExpansions as $expansion)
                <?php /** @var Expansion $expansion */ ?>

            @if($expansion->hasDungeonForGameVersion($currentUserGameVersion, $filterFn))
                <div id="{{ $expansion->shortname }}-grid-content"
                     class="tab-pane fade show {{ $selectedSeasonId === null && $index === 0 ? 'active' : '' }}"
                     role="tabpanel"
                     aria-labelledby="{{ $expansion->shortname }}-grid-content">
                    @php($dungeons = $expansion->dungeons()->active()->forGameVersion($currentUserGameVersion)->get()->filter($filterFn)->values())

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

            @if($expansion->hasRaidForGameVersion($currentUserGameVersion, $filterFn))
                <div id="{{ $expansion->shortname }}-raid-grid-content"
                     class="tab-pane fade show {{ $selectedSeasonId === null && $index === 0 ? 'active' : '' }}"
                     role="tabpanel"
                     aria-labelledby="{{ $expansion->shortname }}-raid-grid-content">
                    @php($raids = $expansion->raids()->active()->forGameVersion($currentUserGameVersion)->get()->filter($filterFn)->values())
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
