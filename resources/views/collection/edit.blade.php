<?php

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection                          $dungeonRouteCollection
 * @var Collection<int, DungeonRouteCollectionGroup>    $editSections
 * @var bool                                            $hasOwnDungeonRoutes
 * @var bool                                            $mayAddDungeonRoutes
 * @var array<int, int>                                 $selectedDungeonRouteIds
 * @var Collection<int, GameVersion>                    $gameVersions
 * @var GameVersion|null                                $selectedGameVersion
 * @var Season|null                                     $selectedSeason
 * @var Collection<int, Team>                           $teams
 * @var Collection<int, DungeonRouteCollectionCategory> $categories
 * @var bool                                            $mayDuplicate
 * @var bool                                            $mayCreateCollection
 * @var GameVersion                                     $duplicateGameVersion
 * @var Collection<int, Season>                         $duplicateSeasons
 * @var array<int|string, int>                          $duplicateMatchingCounts Keyed by season id; '' is free-form.
 */

$duplicateTotal = $dungeonRouteCollection->dungeonRoutes->count();
$duplicateSelectedSeasonId = $dungeonRouteCollection->season_id ?? '';

$title = sprintf(__('view_collection.edit.title'), $dungeonRouteCollection->name);
?>
@extends('layouts.sitepage', [
    'showAds' => false,
    'title' => $title,
    'breadcrumbsParams' => [$dungeonRouteCollection],
])

@section('header-title', $title)
@section('header-addition')
    @if($mayDuplicate)
        <button type="button" class="btn btn-secondary float-end ms-2" data-bs-toggle="modal" data-bs-target="#duplicate_collection_modal">
            <i class="fas fa-clone"></i> {{ __('view_collection.edit.duplicate') }}
        </button>
    @endif
    <a href="{{ route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}"
       class="btn btn-info float-end" role="button">
        <i class="fas fa-external-link-alt"></i> {{ __('view_collection.edit.view_collection') }}
    </a>
@endsection

@section('content')
    @if($mayDuplicate)
        @component('common.general.modal', ['id' => 'duplicate_collection_modal', 'keyboard' => true, 'labelledBy' => 'duplicate_collection_modal_title'])
            <h3 id="duplicate_collection_modal_title" class="card-title">{{ __('view_collection.edit.duplicate_title', ['name' => $dungeonRouteCollection->name]) }}</h3>
            <p class="text-body-secondary">{{ __('view_collection.edit.duplicate_help') }}</p>

            {{ html()->form('POST', route('collections.duplicate', ['dungeonRouteCollection' => $dungeonRouteCollection]))->id('duplicate_collection_form')->open() }}
            @if($duplicateGameVersion->has_seasons)
                <div class="mb-3">
                    {{ html()->label(__('view_collection.edit.duplicate_season'), 'duplicate_season_id') }}
                    <select id="duplicate_season_id" name="season_id" class="form-select">
                        @foreach($duplicateSeasons as $season)
                            <option value="{{ $season->id }}" data-kept="{{ $duplicateMatchingCounts[$season->id] ?? 0 }}"
                                    @selected($season->id === $duplicateSelectedSeasonId)>{{ $season->name_long }}</option>
                        @endforeach
                        <option value="" data-kept="{{ $duplicateMatchingCounts[''] ?? 0 }}"
                                @selected($duplicateSelectedSeasonId === '')>{{ __('view_collection.edit.duplicate_season_none') }}</option>
                    </select>
                </div>
            @endif
            <p id="duplicate_collection_keeps" class="text-body-secondary" aria-live="polite"
               data-text="{{ __('view_collection.edit.duplicate_keeps') }}" data-total="{{ $duplicateTotal }}">
                {{ __('view_collection.edit.duplicate_keeps', ['kept' => $duplicateMatchingCounts[$duplicateSelectedSeasonId] ?? 0, 'total' => $duplicateTotal]) }}
            </p>
            @if($mayCreateCollection)
                {{ html()->input('submit')->value(__('view_collection.edit.duplicate_submit'))->class('btn btn-info') }}
            @else
                {{ html()->input('submit')->value(__('view_collection.edit.duplicate_submit'))->class('btn btn-info')->disabled()->attribute('aria-describedby', 'duplicate_collection_max') }}
                <p id="duplicate_collection_max" class="text-warning mt-2 mb-0">
                    {{ __('view_collection.index.max_collections', ['max' => DungeonRouteCollection::MAX_COLLECTIONS]) }}
                </p>
            @endif
            {{ html()->form()->close() }}
        @endcomponent
    @endif

    <div class="container">
        @include('common.collection.routes', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            'editSections' => $editSections,
            'hasOwnDungeonRoutes' => $hasOwnDungeonRoutes,
            'mayAddDungeonRoutes' => $mayAddDungeonRoutes,
        ])

        <h2 class="h4">{{ __('view_collection.edit.details') }}</h2>
        @include('common.collection.details', [
            'dungeonRouteCollection' => $dungeonRouteCollection,
            'editSections' => $editSections,
            'hasOwnDungeonRoutes' => $hasOwnDungeonRoutes,
            'selectedDungeonRouteIds' => $selectedDungeonRouteIds,
            'gameVersions' => $gameVersions,
            'selectedGameVersion' => $selectedGameVersion,
            'selectedSeason' => $selectedSeason,
            'teams' => $teams,
            'categories' => $categories,
        ])
    </div>
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            let $keeps = $('#duplicate_collection_keeps');
            $('#duplicate_season_id').on('change', function () {
                $keeps.text(`${$keeps.data('text')}`
                    .replace(':kept', $(this).find('option:selected').data('kept'))
                    .replace(':total', $keeps.data('total')));
            });
        });
    </script>
@endsection
