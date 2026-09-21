<?php

use App\Http\Requests\DungeonRoute\DungeonRouteCollectionCreateFormRequest;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, DungeonRouteCollectionGroup>    $editSections
 * @var bool                                            $hasOwnDungeonRoutes
 * @var GameVersion                                     $selectedGameVersion
 * @var Collection<int, Season>                         $seasons
 * @var Season|null                                     $selectedSeason
 * @var Collection<int, Team>                           $teams
 * @var Collection<int, DungeonRouteCollectionCategory> $categories
 * @var Collection<int, string>                         $tagNames
 * @var string|null                                     $selectedTagName
 * @var int                                             $tagDungeonRoutesLeftOut
 * @var string|null                                     $prefillName
 * @var string|null                                     $prefillDescription
 * @var bool                                            $mayCreateCollection
 * @var array<string, string>                           $startFromQuery
 */

// The routes section sits above the form it posts into, so its hidden inputs name that form
$formId = 'collection_details_form';

$tagOptions = ['' => __('view_collection.new.start_from_tag_none')]
    + $tagNames->mapWithKeys(static fn(string $tagName): array => [$tagName => $tagName])->all();
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_collection.new.title')])

@section('header-title', __('view_collection.new.header'))

@section('content')
    <div class="container">
        @if($tagNames->isNotEmpty())
            <div class="mb-3">
                {{ html()->label(__('view_collection.new.start_from_tag'), 'collection_start_from_tag') }}
                {{ html()->select('collection_start_from_tag', $tagOptions, $selectedTagName ?? '')->class('form-select') }}
                <small class="form-text text-body-secondary">
                    {{ __('view_collection.new.start_from_tag_help') }}
                </small>
                @if($tagDungeonRoutesLeftOut > 0)
                    <p id="collection_tag_routes_left_out" class="text-warning mt-1 mb-0" role="status">
                        {{ trans_choice('view_collection.new.tag_routes_left_out', $tagDungeonRoutesLeftOut, ['count' => $tagDungeonRoutesLeftOut]) }}
                    </p>
                @endif
            </div>
        @endif

        @include('common.collection.routes', [
            'dungeonRouteCollection' => null,
            'editSections' => $editSections,
            'hasOwnDungeonRoutes' => $hasOwnDungeonRoutes,
            'mayAddDungeonRoutes' => true,
            'selectedGameVersion' => $selectedGameVersion,
            'selectedSeason' => $selectedSeason,
            'formId' => $formId,
        ])

        <h2 class="h4">{{ __('view_collection.new.details') }}</h2>
        @include('common.collection.details', [
            'dungeonRouteCollection' => null,
            'formId' => $formId,
            'selectedGameVersion' => $selectedGameVersion,
            'seasons' => $seasons,
            'selectedSeason' => $selectedSeason,
            'teams' => $teams,
            'categories' => $categories,
            'prefillName' => $prefillName,
            'prefillDescription' => $prefillDescription,
            'mayCreateCollection' => $mayCreateCollection,
            'formUrlParams' => $startFromQuery,
        ])
    </div>
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            // Starting from a tag reloads the form with that tag's routes for the chosen season
            $('#collection_start_from_tag').on('change', function () {
                let seasonId = $('.collection_season_radios input:checked').val() ?? '';
                let params = new URLSearchParams({
                    season_id: seasonId === '' ? '{{ DungeonRouteCollectionCreateFormRequest::SEASON_NONE }}' : seasonId,
                });
                [['tag', $(this).val()], ['name', $('#name').val()], ['description', $('#description').val()]].forEach(function ([key, value]) {
                    if (value) {
                        params.set(key, value);
                    }
                });

                window.location.href = `{{ route('collections.new') }}?${params.toString()}`;
            });
        });
    </script>
@endsection
