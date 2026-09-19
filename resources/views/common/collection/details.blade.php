<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\PublishedState;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection|null                            $dungeonRouteCollection
 * @var Collection<int, DungeonRoute>                          $ownDungeonRoutes
 * @var array<int, int>                                        $selectedDungeonRouteIds
 * @var Collection<int, Team>                                  $teams
 * @var Collection<int, DungeonRouteCollectionCategory>        $categories
 */

$dungeonRouteCollection  ??= null;
$selectedDungeonRouteIds ??= [];
$teams                   ??= collect();
$categories              ??= collect();

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

<div class="mb-3">
    @if($ownDungeonRoutes->isEmpty())
        {{ html()->label(__('view_common.collection.details.dungeon_routes'), 'dungeon_routes') }}
        <p class="text-body-secondary">
            {{ __('view_common.collection.details.dungeon_routes_none') }}
        </p>
    @else
        @include('common.forms.orderedselect', [
            'id' => 'dungeon_routes',
            'name' => 'dungeon_routes',
            'label' => __('view_common.collection.details.dungeon_routes'),
            'options' => $ownDungeonRoutes->mapWithKeys(static fn(DungeonRoute $ownDungeonRoute): array => [
                $ownDungeonRoute->id => sprintf('%s — %s', $ownDungeonRoute->title, __($ownDungeonRoute->dungeon?->name ?? '')),
            ])->all(),
            'selectedIds' => $selectedDungeonRouteIds,
            'max' => DungeonRouteCollection::MAX_ROUTES,
            'help' => __('view_common.collection.details.dungeon_routes_help'),
            'emptyText' => __('view_common.collection.details.dungeon_routes_empty'),
        ])
    @endif
</div>

{{ html()->input('submit')->value($dungeonRouteCollection !== null ? __('view_common.collection.details.save') : __('view_common.collection.details.submit'))->class('btn btn-info') }}

{{ html()->closeModelForm() }}

@isset($dungeonRouteCollection)
    {{ html()->form('DELETE', route('collections.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]))->class('mt-4')->open() }}
    {{ html()->input('submit')->value(__('view_common.collection.details.delete'))->class('btn btn-danger') }}
    {{ html()->closeModelForm() }}
@endisset
