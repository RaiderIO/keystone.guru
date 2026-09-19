<?php

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Team;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, DungeonRouteCollectionGroup>    $editSections
 * @var bool                                            $hasOwnDungeonRoutes
 * @var Collection<int, GameVersion>                    $gameVersions
 * @var GameVersion                                     $selectedGameVersion
 * @var Collection<int, Collection<int, Season>>        $seasonsPerGameVersion
 * @var Season|null                                     $selectedSeason
 * @var Collection<int, Team>                           $teams
 * @var Collection<int, DungeonRouteCollectionCategory> $categories
 * @var array<int, int>                                 $selectedDungeonRouteIds
 * @var Collection<int, string>                         $tagNames
 * @var string|null                                     $selectedTagName
 * @var int                                             $tagDungeonRoutesLeftOut
 * @var string|null                                     $prefillName
 * @var string|null                                     $prefillDescription
 * @var bool                                            $mayCreateCollection
 */

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

        @include('common.collection.details', [
            'dungeonRouteCollection' => null,
            'editSections' => $editSections,
            'hasOwnDungeonRoutes' => $hasOwnDungeonRoutes,
            'selectedDungeonRouteIds' => $selectedDungeonRouteIds,
            'gameVersions' => $gameVersions,
            'selectedGameVersion' => $selectedGameVersion,
            'seasonsPerGameVersion' => $seasonsPerGameVersion,
            'selectedSeason' => $selectedSeason,
            'teams' => $teams,
            'categories' => $categories,
            'prefillName' => $prefillName,
            'prefillDescription' => $prefillDescription,
            'mayCreateCollection' => $mayCreateCollection,
        ])
    </div>
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            let $gameVersion = $('#game_version_id');
            let initialKind = null;

            // The route picker is built for one game version and season; once either changes it would offer the wrong routes
            function applyKind() {
                let gameVersionId = $gameVersion.val();
                $('.collection_season').each(function () {
                    let isSelected = String($(this).data('game-version-id')) === gameVersionId;
                    $(this).prop('hidden', !isSelected).find('select').prop('disabled', !isSelected);
                });

                let kind = `${gameVersionId}-${$('.collection_season:not([hidden]) select').val() ?? ''}`;
                initialKind ??= kind;
                $('#collection_dungeon_routes').prop('hidden', kind !== initialKind).find('input').prop('disabled', kind !== initialKind);
                $('#collection_dungeon_routes_kind_changed').prop('hidden', kind === initialKind);
            }

            $gameVersion.on('change', applyKind);
            $('.collection_season select').on('change', applyKind);
            applyKind();

            // Starting from a tag reloads the form with that tag's routes for the chosen game version and season
            $('#collection_start_from_tag').on('change', function () {
                let params = new URLSearchParams({
                    game_version_id: $gameVersion.val(),
                    season_id: $('.collection_season:not([hidden]) select').val() ?? '',
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
