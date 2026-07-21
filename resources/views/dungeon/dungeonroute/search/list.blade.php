<?php

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\Season\SeasonService;
use Illuminate\Support\Collection;

/**
 * @var GameVersion $gameVersion
 */

?>
@extends('layouts.sitepage', [
    'rootClass' => 'discover',
    'title' => __('view_dungeon.dungeonroute.search.list.title'),
])

@section('header-title')
    {{ __('view_dungeon.dungeonroute.search.list.header') }}
@endsection

@section('content')

    <div class="row mb-3">
        <div class="col">
            <p>
                {{ __('view_dungeon.dungeonroute.search.list.description') }}
            </p>
        </div>
    </div>

    @include('common.dungeon.gridtabs', [
        'id' => 'search_dungeon',
        'tabsId' => 'search_dungeon_select_tabs',
        'route' => 'dungeon.dungeonroute.search.gameversion.dungeon',
        'routeParams' => ['gameVersion' => $gameVersion],
    ])
@endsection
