<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Team;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection|null                            $dungeonRouteCollection
 * @var Collection<int, DungeonRouteCollectionGroup>           $editSections
 * @var bool                                                   $hasOwnDungeonRoutes
 * @var array<int, int>                                        $selectedDungeonRouteIds
 * @var Collection<int, GameVersion>                           $gameVersions
 * @var GameVersion|null                                       $selectedGameVersion
 * @var Collection<int, Collection<int, Season>>               $seasonsPerGameVersion Keyed by game version id; new collections only.
 * @var Season|null                                            $selectedSeason
 * @var Collection<int, Team>                                  $teams
 * @var Collection<int, DungeonRouteCollectionCategory>        $categories
 */

$dungeonRouteCollection  ??= null;
$selectedDungeonRouteIds ??= [];
$seasonsPerGameVersion   ??= collect();
$selectedSeason          ??= null;
$teams                   ??= collect();
$categories              ??= collect();

$isNew = $dungeonRouteCollection === null;
// The game version is fixed once a route is in the collection
$mayChangeGameVersion = $isNew || $dungeonRouteCollection->dungeonRoutes->isEmpty();

$gameVersionOptions = $gameVersions->mapWithKeys(static fn(GameVersion $gameVersion): array => [
    $gameVersion->id => __($gameVersion->name),
])->all();

/** @var Collection<int, array<int|string, string>> $seasonOptionsPerGameVersion */
$seasonOptionsPerGameVersion = $seasonsPerGameVersion->map(static fn(Collection $seasons): array => ['' => __('view_common.collection.details.season_none')] + $seasons->mapWithKeys(static fn(Season $season): array => [
    $season->id => $season->name_long,
])->all());

// After a failed validation the picker must show what was submitted, not what is stored - otherwise
// resubmitting the corrected form saves the collection with no routes. An empty submission stays empty
if (session()->hasOldInput()) {
    $selectedDungeonRouteIds = array_map(intval(...), (array)old('dungeon_routes', []));
}

// Sharing with a team is only meaningful when the user is actually in one
$availablePublishedStates = array_values(array_filter(
    DungeonRouteCollection::AVAILABLE_PUBLISHED_STATES,
    static fn(string $publishedState): bool => $publishedState !== PublishedState::TEAM || $teams->isNotEmpty(),
));

$categoryOptions = [null => __('view_common.collection.details.category_none')];
foreach ($categories as $category) {
    $categoryOptions[$category->id] = $category->getTranslatedName();
}

$teamOptions = [null => __('view_common.collection.details.team_none')];
foreach ($teams as $team) {
    $teamOptions[$team->id] = $team->name;
}
?>

@isset($dungeonRouteCollection)
    {{ html()->modelForm($dungeonRouteCollection, 'PATCH', route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]))->open() }}
@else
    {{ html()->form('POST', route('collections.savenew'))->open() }}
@endisset

<div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.collection.details.name') . '<span class="form-required">*</span>', 'name') }}
    {{ html()->text('name', $dungeonRouteCollection?->name)->class('form-control')->attribute('maxlength', 128) }}
    @include('common.forms.form-error', ['key' => 'name'])
</div>

<div class="mb-3{{ $errors->has('description') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.collection.details.description'), 'description') }}
    {{ html()->textarea('description', $dungeonRouteCollection?->description)
        ->class('form-control')
        ->rows(3)
        ->attribute('maxlength', 1000) }}
    @include('common.forms.form-error', ['key' => 'description'])
</div>

<div class="mb-3{{ $errors->has('game_version_id') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.collection.details.game_version'), 'game_version_id') }}
    @if($mayChangeGameVersion)
        {{ html()->select('game_version_id', $gameVersionOptions, $selectedGameVersion?->id)->class('form-select') }}
        <small class="form-text text-body-secondary">
            {{ __('view_common.collection.details.game_version_help') }}
        </small>
    @else
        <p id="game_version_id" class="form-control-plaintext mb-0">
            {{ __($selectedGameVersion?->name ?? GameVersion::getDefaultGameVersion()->name) }}
        </p>
        <small class="form-text text-body-secondary">
            {{ __('view_common.collection.details.game_version_fixed') }}
        </small>
    @endif
    @include('common.forms.form-error', ['key' => 'game_version_id'])
</div>

@if($isNew)
    {{-- One season field per game version with seasons; only the selected game version's is shown and posted --}}
    @foreach($seasonOptionsPerGameVersion as $gameVersionId => $seasonOptions)
        @php($isSelectedGameVersion = $gameVersionId === $selectedGameVersion?->id)
        <div class="mb-3 collection_season{{ $errors->has('season_id') ? ' has-error' : '' }}"
             data-game-version-id="{{ $gameVersionId }}" @if(!$isSelectedGameVersion) hidden @endif>
            {{ html()->label(__('view_common.collection.details.season'), sprintf('season_id_%d', $gameVersionId)) }}
            {{ html()->select('season_id', $seasonOptions, $isSelectedGameVersion ? $selectedSeason?->id : '')
                ->id(sprintf('season_id_%d', $gameVersionId))
                ->class('form-select')
                ->disabled(!$isSelectedGameVersion) }}
            <small class="form-text text-body-secondary">
                {{ __('view_common.collection.details.season_help') }}
            </small>
            @include('common.forms.form-error', ['key' => 'season_id'])
        </div>
    @endforeach
@elseif($dungeonRouteCollection->isSeasonSet() && $selectedSeason !== null)
    <div class="mb-3{{ $errors->has('season_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_common.collection.details.season'), 'season_id') }}
        {{ html()->select('season_id', [
            $selectedSeason->id => $selectedSeason->name_long,
            '' => __('view_common.collection.details.season_none'),
        ], $selectedSeason->id)->class('form-select') }}
        <small class="form-text text-body-secondary">
            {{ __('view_common.collection.details.season_make_free_form') }}
        </small>
        @include('common.forms.form-error', ['key' => 'season_id'])
    </div>
@endif

<div class="mb-3{{ $errors->has('category_id') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.collection.details.category'), 'category_id') }}
    {{ html()->select('category_id', $categoryOptions, $dungeonRouteCollection?->dungeon_route_collection_category_id)->class('form-select') }}
    <small class="form-text text-body-secondary">
        {{ __('view_common.collection.details.category_help') }}
    </small>
    @include('common.forms.form-error', ['key' => 'category_id'])
</div>

<div class="mb-3{{ $errors->has('published_state') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.collection.details.published_state'), 'published_state') }}
    @include('common.forms.publishedstate', [
        'id' => 'published_state',
        'name' => 'published_state',
        'publishedStates' => DungeonRouteCollection::AVAILABLE_PUBLISHED_STATES,
        'availablePublishedStates' => $availablePublishedStates,
        'selected' => old('published_state', $dungeonRouteCollection?->getPublishedStateName() ?? PublishedState::UNPUBLISHED),
        'subtexts' => collect(DungeonRouteCollection::AVAILABLE_PUBLISHED_STATES)->mapWithKeys(static fn(string $publishedState): array => [
            $publishedState => __(sprintf('view_collection.published_state_subtext.%s', $publishedState)),
        ])->all(),
    ])
    <small class="form-text text-body-secondary">
        {{ __('view_common.collection.details.published_state_help') }}
    </small>
    @include('common.forms.form-error', ['key' => 'published_state'])
</div>

@if($teams->isNotEmpty())
    <div class="mb-3{{ $errors->has('team_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_common.collection.details.team'), 'team_id') }}
        {{ html()->select('team_id', $teamOptions, $dungeonRouteCollection?->team_id)->class('form-select') }}
        <small class="form-text text-body-secondary">
            {{ __('view_common.collection.details.team_help') }}
        </small>
        @include('common.forms.form-error', ['key' => 'team_id'])
    </div>
@endif

@if($isNew)
<div id="collection_dungeon_routes" class="mb-3">
    @if(!$hasOwnDungeonRoutes)
        {{ html()->label(__('view_common.collection.details.dungeon_routes'), 'dungeon_routes') }}
        <p class="text-body-secondary">
            {{ __('view_common.collection.details.dungeon_routes_none') }}
        </p>
    @else
        @foreach($editSections as $editSection)
            <div class="mb-3">
                @if(!$editSection->matchesCollection)
                    @include('common.forms.orderedselect', [
                        'id' => 'dungeon_routes_foreign',
                        'name' => 'dungeon_routes',
                        'label' => __('view_common.collection.details.dungeon_routes_foreign'),
                        'labelClass' => 'form-label fw-bold',
                        'options' => $editSection->dungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
                            $dungeonRoute->id => sprintf('%s — %s', $dungeonRoute->title, __($dungeonRoute->dungeon?->name ?? '')),
                        ])->all(),
                        'selectedIds' => $selectedDungeonRouteIds,
                        'max' => DungeonRouteCollection::MAX_ROUTES,
                        'help' => __('view_common.collection.details.dungeon_routes_foreign_help'),
                        'emptyText' => __('view_common.collection.details.dungeon_routes_empty'),
                    ])
                @elseif($editSection->dungeon !== null)
                    @include('common.forms.orderedselect', [
                        'id' => sprintf('dungeon_routes_%d', $editSection->dungeon->id),
                        'name' => 'dungeon_routes',
                        'label' => __($editSection->dungeon->name),
                        'labelClass' => 'form-label fw-bold',
                        'options' => $editSection->dungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
                            $dungeonRoute->id => $dungeonRoute->title,
                        ])->all(),
                        'selectedIds' => $selectedDungeonRouteIds,
                        'max' => DungeonRouteCollection::MAX_ROUTES,
                        'help' => __('view_common.collection.details.dungeon_routes_slot_help', ['dungeon' => __($editSection->dungeon->name)]),
                        'emptyText' => __('view_common.collection.details.dungeon_routes_slot_empty', ['dungeon' => __($editSection->dungeon->name)]),
                    ])
                @else
                    @include('common.forms.orderedselect', [
                        'id' => 'dungeon_routes',
                        'name' => 'dungeon_routes',
                        'label' => __('view_common.collection.details.dungeon_routes'),
                        'options' => $editSection->dungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
                            $dungeonRoute->id => sprintf('%s — %s', $dungeonRoute->title, __($dungeonRoute->dungeon?->name ?? '')),
                        ])->all(),
                        'selectedIds' => $selectedDungeonRouteIds,
                        'max' => DungeonRouteCollection::MAX_ROUTES,
                        'help' => __('view_common.collection.details.dungeon_routes_help'),
                        'emptyText' => __('view_common.collection.details.dungeon_routes_empty'),
                    ])
                @endif
            </div>
        @endforeach
        @foreach(collect($errors->get('dungeon_routes.*'))->flatten()->unique() as $dungeonRoutesError)
            <div class="invalid-feedback d-block" role="alert">
                <strong>{{ $dungeonRoutesError }}</strong>
            </div>
        @endforeach
    @endif
</div>

    <p id="collection_dungeon_routes_kind_changed" class="text-body-secondary" hidden>
        {{ __('view_common.collection.details.kind_changed') }}
    </p>
@endif

{{ html()->input('submit')->value($dungeonRouteCollection !== null ? __('view_common.collection.details.save') : __('view_common.collection.details.submit'))->class('btn btn-info') }}

{{ html()->closeModelForm() }}

@isset($dungeonRouteCollection)
    {{ html()->form('DELETE', route('collections.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]))->class('mt-4')->open() }}
    {{ html()->input('submit')->value(__('view_common.collection.details.delete'))->class('btn btn-danger') }}
    {{ html()->closeModelForm() }}
@endisset
