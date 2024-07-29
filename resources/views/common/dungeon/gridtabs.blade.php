<?php
/**
 * @var \App\Models\GameVersion\GameVersion                   $currentUserGameVersion
 * @var \App\Models\Season|null                               $nextSeason
 * @var \App\Models\Season                                    $currentSeason
 * @var \Illuminate\Support\Collection<\App\Models\Expansion> $activeExpansions
 * @var string                                                $id
 * @var string                                                $tabsId
 * @var bool                                                  $selectable
 * @var callable|null                                         $subtextFn
 */
$selectedSeasonId = $currentUserGameVersion->has_seasons ? ($nextSeason ?? $currentSeason)->id : null;
$selectable       ??= true;
$route            ??= null;
$routeParams      ??= [];
$linkMapFn        = static fn(\App\Models\Dungeon $dungeon) => [
    'dungeon' => $dungeon->key,
    'link'    => route($route, array_merge($routeParams, ['dungeon' => $dungeon])),
];
$subtextFn        ??= null;

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
                    >{{ $nextSeason->name }}</a>
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
                <?php
                /** @var \App\Models\Expansion $expansion */ ?>
            @if($expansion->hasDungeonForGameVersion($currentUserGameVersion))
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
        @endforeach
    </ul>

    <div class="tab-content">
        @if($currentUserGameVersion->has_seasons)
            @if($nextSeason !== null)
                <div id="season-{{ $nextSeason->id }}-grid-content"
                     class="tab-pane fade show {{ $selectedSeasonId === $nextSeason->id ? 'active' : '' }}"
                     role="tabpanel"
                     aria-labelledby="season-{{ $nextSeason->id }}-grid-content">
                    @include('common.dungeon.grid', [
                        'dungeons' => $nextSeason->dungeons,
                        'names' => true,
                        'selectable' => true,
                        'route' => $route,
                        'links' => $route === null ? collect() : $nextSeason->dungeons->map($linkMapFn),
                        'subtextFn' => $subtextFn,
                    ])
                </div>
            @endif
            <div id="season-{{ $currentSeason->id }}-grid-content"
                 class="tab-pane fade show {{ $selectedSeasonId === $currentSeason->id ? 'active' : '' }}"
                 role="tabpanel"
                 aria-labelledby="season-{{ $currentSeason->id }}-grid-content">
                @include('common.dungeon.grid', [
                    'dungeons' => $currentSeason->dungeons,
                    'names' => true,
                    'selectable' => true,
                    'route' => $route,
                    'links' => $route === null ? collect() : $currentSeason->dungeons->map($linkMapFn),
                    'subtextFn' => $subtextFn,
                ])
            </div>
        @endif
        <?php
        $index = 0; ?>
        @foreach($activeExpansions as $expansion)
                <?php
                /** @var \App\Models\Expansion $expansion */ ?>
            @if($expansion->hasDungeonForGameVersion($currentUserGameVersion))
                <div id="{{ $expansion->shortname }}-grid-content"
                     class="tab-pane fade show {{ $selectedSeasonId === null && $index === 0 ? 'active' : '' }}"
                     role="tabpanel"
                     aria-labelledby="{{ $expansion->shortname }}-grid-content">
                    @include('common.dungeon.grid', [
                        'expansion' => $expansion,
                        'names' => true,
                        'selectable' => true,
                        'route' => $route,
                        'links' => $route === null ? collect() : $expansion->dungeons()->active()->get()->map($linkMapFn),
                        'subtextFn' => $subtextFn,
                    ])
                </div>
                @php($index++)
            @endif
        @endforeach
    </div>
</div>
