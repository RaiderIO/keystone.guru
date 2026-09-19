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
 * @var array<int, array{text: string, isWarning: bool}>       $enemyForcesDetails Keyed by route id.
 * @var Collection<int, Team>                                  $teams
 * @var Collection<int, DungeonRouteCollectionCategory>        $categories
 */

$dungeonRouteCollection  ??= null;
$selectedDungeonRouteIds ??= [];
$seasonsPerGameVersion   ??= collect();
$selectedSeason          ??= null;
$enemyForcesDetails      ??= [];
$teams                   ??= collect();
$categories              ??= collect();

$isNew = $dungeonRouteCollection === null;
// The game version is fixed once a route is in the collection
$mayChangeGameVersion = $isNew || $dungeonRouteCollection->game_version_id === null || $dungeonRouteCollection->dungeonRoutes->isEmpty();

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

<fieldset class="mb-3{{ $errors->has('game_version_id') ? ' has-error' : '' }}">
    <legend class="form-label fs-6">{{ __('view_common.collection.details.game_version') }}</legend>
    @if($mayChangeGameVersion)
        <div id="collection_game_version_container" class="btn-group flex-wrap" role="group">
            @foreach($gameVersions as $gameVersion)
                <input type="radio" name="game_version_id" id="game_version_id_{{ $gameVersion->id }}"
                       class="btn-check collection_game_version" value="{{ $gameVersion->id }}"
                    @checked($gameVersion->id === $selectedGameVersion?->id)>
                <label class="btn btn-secondary" for="game_version_id_{{ $gameVersion->id }}">
                    {{ __($gameVersion->name) }}
                </label>
            @endforeach
        </div>
    @else
        <p id="game_version_id" class="form-control-plaintext py-0 mb-0">
            {{ __($selectedGameVersion?->name ?? GameVersion::getDefaultGameVersion()->name) }}
            <small class="text-body-secondary">&middot; {{ __('view_common.collection.details.game_version_fixed') }}</small>
        </p>
    @endif
    @include('common.forms.form-error', ['key' => 'game_version_id'])
</fieldset>

@if($isNew)
    {{-- One season field per game version with seasons; only the selected game version's is shown and posted --}}
    @foreach($seasonsPerGameVersion as $gameVersionId => $seasons)
        @php($isSelectedGameVersion = $gameVersionId === $selectedGameVersion?->id)
        <fieldset class="mb-3 collection_season{{ $errors->has('season_id') ? ' has-error' : '' }}"
                  data-game-version-id="{{ $gameVersionId }}" @if(!$isSelectedGameVersion) hidden @endif
                  aria-describedby="season_help_{{ $gameVersionId }}">
            <legend class="form-label fs-6">{{ __('view_common.collection.details.season') }}</legend>
            @include('common.collection.seasonradios', [
                'idPrefix' => sprintf('season_id_%d', $gameVersionId),
                'seasons' => $seasons,
                'selectedSeasonId' => $isSelectedGameVersion ? $selectedSeason?->id : null,
                'disabled' => !$isSelectedGameVersion,
            ])
            <small id="season_help_{{ $gameVersionId }}" class="form-text text-body-secondary d-block">
                {{ __('view_common.collection.details.season_help') }}
            </small>
            @include('common.forms.form-error', ['key' => 'season_id'])
        </fieldset>
    @endforeach
@elseif($dungeonRouteCollection->isSeasonSet() && $selectedSeason !== null)
    <fieldset class="mb-3{{ $errors->has('season_id') ? ' has-error' : '' }}" aria-describedby="season_help">
        <legend class="form-label fs-6">{{ __('view_common.collection.details.season') }}</legend>
        @include('common.collection.seasonradios', [
            'idPrefix' => 'season_id',
            'seasons' => collect([$selectedSeason]),
            'selectedSeasonId' => $selectedSeason->id,
            'disabled' => false,
        ])
        <small id="season_help" class="form-text text-body-secondary d-block">
            {{ __('view_common.collection.details.season_make_free_form') }}
        </small>
        @include('common.forms.form-error', ['key' => 'season_id'])
    </fieldset>
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

<div id="collection_dungeon_routes" class="mb-3">
    @if(!$hasOwnDungeonRoutes)
        {{ html()->label(__('view_common.collection.details.dungeon_routes'), 'dungeon_routes') }}
        <p class="text-body-secondary">
            {{ __('view_common.collection.details.dungeon_routes_none') }}
        </p>
    @else
        @php($slotSections = $editSections->filter(static fn(DungeonRouteCollectionGroup $editSection): bool => $editSection->matchesCollection && $editSection->dungeon !== null))
        <p class="form-text text-body-secondary mt-0">
            {{ __('view_common.collection.details.dungeon_routes_help') }}
        </p>

        @if($slotSections->isNotEmpty())
            <div class="row row-cols-1 row-cols-lg-2 g-3 mb-3">
                @foreach($slotSections as $editSection)
                    <div class="col">
                        <div class="collection_slot h-100" style="background-image: url('{{ $editSection->dungeon->getImageUrl() }}')">
                            <div class="collection_slot_scrim h-100 p-3">
                                @include('common.forms.orderedselect', [
                                    'id' => sprintf('dungeon_routes_%d', $editSection->dungeon->id),
                                    'name' => 'dungeon_routes',
                                    'label' => __($editSection->dungeon->name),
                                    'labelClass' => 'form-label fw-bold',
                                    'options' => $editSection->dungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
                                        $dungeonRoute->id => $dungeonRoute->title,
                                    ])->all(),
                                    'optionDetails' => $enemyForcesDetails,
                                    'detailWarningText' => __('view_common.collection.details.enemy_forces_short'),
                                    'selectedIds' => $selectedDungeonRouteIds,
                                    'max' => DungeonRouteCollection::MAX_ROUTES,
                                    'emptyText' => __('view_common.collection.details.dungeon_routes_slot_empty', ['dungeon' => __($editSection->dungeon->name)]),
                                ])
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @foreach($editSections as $editSection)
            @if(!$editSection->matchesCollection)
                <div class="mb-3">
                    @include('common.forms.orderedselect', [
                        'id' => 'dungeon_routes_foreign',
                        'name' => 'dungeon_routes',
                        'label' => __('view_common.collection.details.dungeon_routes_foreign'),
                        'labelClass' => 'form-label fw-bold',
                        'options' => $editSection->dungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
                            $dungeonRoute->id => sprintf('%s — %s', $dungeonRoute->title, __($dungeonRoute->dungeon?->name ?? '')),
                        ])->all(),
                        'optionDetails' => $enemyForcesDetails,
                        'detailWarningText' => __('view_common.collection.details.enemy_forces_short'),
                        'selectedIds' => $selectedDungeonRouteIds,
                        'max' => DungeonRouteCollection::MAX_ROUTES,
                        'help' => __('view_common.collection.details.dungeon_routes_foreign_help'),
                        'emptyText' => __('view_common.collection.details.dungeon_routes_empty'),
                    ])
                </div>
            @elseif($editSection->dungeon === null)
                <div class="mb-3">
                    @include('common.forms.orderedselect', [
                        'id' => 'dungeon_routes',
                        'name' => 'dungeon_routes',
                        'label' => __('view_common.collection.details.dungeon_routes'),
                        'options' => $editSection->dungeonRoutes->mapWithKeys(static fn(DungeonRoute $dungeonRoute): array => [
                            $dungeonRoute->id => sprintf('%s — %s', $dungeonRoute->title, __($dungeonRoute->dungeon?->name ?? '')),
                        ])->all(),
                        'optionDetails' => $enemyForcesDetails,
                        'detailWarningText' => __('view_common.collection.details.enemy_forces_short'),
                        'selectedIds' => $selectedDungeonRouteIds,
                        'max' => DungeonRouteCollection::MAX_ROUTES,
                        'emptyText' => __('view_common.collection.details.dungeon_routes_empty'),
                    ])
                </div>
            @endif
        @endforeach
        @foreach(collect($errors->get('dungeon_routes.*'))->flatten()->unique() as $dungeonRoutesError)
            <div class="invalid-feedback d-block" role="alert">
                <strong>{{ $dungeonRoutesError }}</strong>
            </div>
        @endforeach
    @endif
</div>

@if($isNew)
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
