<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use Illuminate\Support\Collection;

/**
 * @var AffixGroup|null                                       $currentAffixGroup
 * @var Dungeon                                               $dungeon
 * @var GameVersion                                           $gameVersion
 * @var Collection<int, WeeklyRoute>                          $weeklyRoutes
 * @var Collection<int, DungeonRoute>                         $popularRoutes
 * @var Collection<int, DungeonRoute>                         $userRoutes
 * @var array{npc: int, spell: int, pull_count: int, avg_enemies_per_pull: float} $dungeonStats
 * @var Collection<int, Dungeon>                              $gameVersionDungeons
 */

$searchLink = route('dungeon.dungeonroute.search.gameversion.dungeon', [
    'gameVersion' => $gameVersion,
    'dungeon'     => $dungeon,
]);

$compendiumCards = [
    [
        'icon'     => 'fa-dragon',
        'title'    => __('view_dungeonroute.discover.dungeon.overview.compendium.npc.title'),
        'text'     => __('view_dungeonroute.discover.dungeon.overview.compendium.npc.description'),
        'cta'      => __('view_dungeonroute.discover.dungeon.overview.compendium.npc.cta'),
        'route'    => route('npc.compendium.index'),
        'subtitle' => sprintf('%s %s', number_format($dungeonStats['npc']), __('view_dungeonroute.discover.dungeon.overview.compendium.npc.count_suffix')),
    ],
    [
        'icon'     => 'fa-magic',
        'title'    => __('view_dungeonroute.discover.dungeon.overview.compendium.spell.title'),
        'text'     => __('view_dungeonroute.discover.dungeon.overview.compendium.spell.description'),
        'cta'      => __('view_dungeonroute.discover.dungeon.overview.compendium.spell.cta'),
        'route'    => route('spell.compendium.index'),
        'subtitle' => sprintf('%s %s', number_format($dungeonStats['spell']), __('view_dungeonroute.discover.dungeon.overview.compendium.spell.count_suffix')),
    ],
    [
        'icon'     => 'fa-stream',
        'title'    => __('view_dungeonroute.discover.dungeon.overview.compendium.activity.title'),
        'text'     => __('view_dungeonroute.discover.dungeon.overview.compendium.activity.description'),
        'cta'      => __('view_dungeonroute.discover.dungeon.overview.compendium.activity.cta'),
        'route'    => route('compendium.activity', ['dungeon' => $dungeon]),
        'subtitle' => __('view_dungeonroute.discover.dungeon.overview.compendium.activity.subtitle'),
    ],
];
?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'title' => __($dungeon->name),
    'dungeonContextLinks' => $gameVersionDungeons->mapWithKeys(fn (Dungeon $dungeon) => [
        $dungeon->key => route('dungeonroutes.discoverdungeon', [
            'gameVersion' => $gameVersion,
            'dungeon' => $dungeon,
        ])
    ]),
])

@include('common.general.inline', ['path' => 'dungeonroute/discover/discover',
        'options' =>  [
        ],
])

@section('scripts')
    @parent

    @include('common.handlebars.affixgroups')
@endsection

@section('content')
    @include('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])

    <div class="row mt-2">
        <div class="col-xl text-center">
            <h1 class="mb-0">
                <img src="{{ ksgAssetImage(sprintf('expansions/%s.png', $dungeon->expansion->shortname)) }}"
                     alt="{{ __($dungeon->expansion->name) }}"
                     title="{{ __($dungeon->expansion->name) }}"
                     class="mr-2" style="height: 14px; width: auto; vertical-align: middle;">
                {{ __($dungeon->name) }}
            </h1>

            @if($dungeonStats['pull_count'] > 0)
                <div class="row justify-content-center mt-3">
                    <div class="col-auto text-center px-4">
                        <div class="h3 mb-0">{{ number_format($dungeonStats['pull_count']) }}</div>
                        <div class="text-muted small">{{ __('view_dungeonroute.discover.dungeon.overview.stats.groups') }}</div>
                    </div>
                    <div class="col-auto text-center px-4">
                        <div class="h3 mb-0">{{ $dungeonStats['avg_enemies_per_pull'] }}</div>
                        <div class="text-muted small">{{ __('view_dungeonroute.discover.dungeon.overview.stats.avg_enemies') }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($weeklyRoutes->isNotEmpty())
        <div class="row mt-4">
            <div class="col-xl">
                <h2 class="text-center">{{ __('view_dungeonroute.discover.dungeon.overview.featured_title') }}</h2>
            </div>
        </div>
        <div class="row">
            @foreach($weeklyRoutes as $weeklyRoute)
                @continue($weeklyRoute->dungeonRoute === null)
                <div class="col-12 col-xl-4">
                    <h5 class="text-center mt-3">
                        {{ __(sprintf('view_dungeonroute.discover.dungeon.overview.featured.%s', $weeklyRoute->type)) }}
                    </h5>
                    @include('common.dungeonroute.cardvertical', [
                        'dungeonroute' => $weeklyRoute->dungeonRoute,
                        'currentAffixGroup' => $currentAffixGroup,
                        'tierAffixGroup' => null,
                        'showDungeonImage' => false,
                        'cache' => true,
                    ])
                </div>
            @endforeach
        </div>
    @endif

    <div class="row mt-5">
        <div class="col-xl text-center">
            <h2>{{ __('view_dungeonroute.discover.dungeon.overview.compendium.title') }}</h2>
            <p class="text-muted">{{ __('view_dungeonroute.discover.dungeon.overview.compendium.subtitle') }}</p>
        </div>
    </div>
    <div class="row">
        @foreach($compendiumCards as $card)
            <div class="col-12 col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-3">
                            <i class="fas {{ $card['icon'] }} fa-3x"></i>
                        </div>
                        <h5 class="card-title font-weight-bold">{{ $card['title'] }}</h5>
                        <div class="text-muted small mb-2">{{ $card['subtitle'] }}</div>
                        <p class="card-text flex-grow-1">{{ $card['text'] }}</p>
                        <a href="{{ $card['route'] }}" class="btn btn-primary mt-2">
                            {{ $card['cta'] }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($userRoutes->isNotEmpty())
        <div class="row mt-4">
            <div class="col-xl">
                <h2 class="text-center">{{ __('view_dungeonroute.discover.dungeon.overview.your_routes') }}</h2>
                @include('common.dungeonroute.cardlist', [
                    'cols' => 4,
                    'currentAffixGroup' => $currentAffixGroup,
                    'dungeonroutes' => $userRoutes,
                ])
            </div>
        </div>
    @endif

    <div class="row mt-4">
        <div class="col-xl">
            <h2 class="text-center">{{ __('view_dungeonroute.discover.dungeon.overview.popular') }}</h2>
            @include('common.dungeonroute.cardlist', [
                'cols' => 4,
                'currentAffixGroup' => $currentAffixGroup,
                'dungeonroutes' => $popularRoutes,
            ])
        </div>
    </div>

    <div class="row mt-4 mb-4">
        <div class="col-xl text-center">
            <a href="{{ $searchLink }}" class="btn btn-primary">
                {{ __('view_dungeonroute.discover.dungeon.overview.browse_all') }} <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
