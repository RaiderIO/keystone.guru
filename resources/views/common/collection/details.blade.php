<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\PublishedState;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection|null   $dungeonRouteCollection
 * @var Collection<int, DungeonRoute> $ownDungeonRoutes
 * @var array<int, int>               $selectedDungeonRouteIds
 * @var Collection<int, Team>         $teams
 */

$dungeonRouteCollection  ??= null;
$selectedDungeonRouteIds ??= [];
$teams                   ??= collect();

$publishedStateOptions = [];
foreach (DungeonRouteCollection::AVAILABLE_PUBLISHED_STATES as $publishedState) {
    // Sharing with a team is only meaningful when the user is actually in one
    if ($publishedState === PublishedState::TEAM && $teams->isEmpty()) {
        continue;
    }

    $publishedStateOptions[$publishedState] = __(sprintf('view_collection.published_state.%s', $publishedState));
}

$teamOptions = [null => __('view_collection.details.team_none')];
foreach ($teams as $team) {
    $teamOptions[$team->id] = $team->name;
}
?>

@include('common.general.messages')

@isset($dungeonRouteCollection)
    {{ html()->modelForm($dungeonRouteCollection, 'PATCH', route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]))->open() }}
@else
    {{ html()->form('POST', route('collections.savenew'))->open() }}
@endisset

<div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
    {{ html()->label(__('view_collection.details.name') . '<span class="form-required">*</span>', 'name') }}
    {{ html()->text('name', $dungeonRouteCollection?->name)->class('form-control')->attribute('maxlength', 128) }}
    @include('common.forms.form-error', ['key' => 'name'])
</div>

<div class="mb-3{{ $errors->has('description') ? ' has-error' : '' }}">
    {{ html()->label(__('view_collection.details.description'), 'description') }}
    {{ html()->textarea('description', $dungeonRouteCollection?->description)
        ->class('form-control')
        ->rows(3)
        ->attribute('maxlength', 1000) }}
    @include('common.forms.form-error', ['key' => 'description'])
</div>

<div class="mb-3{{ $errors->has('published_state') ? ' has-error' : '' }}">
    {{ html()->label(__('view_collection.details.published_state'), 'published_state') }}
    {{ html()->select('published_state', $publishedStateOptions, $dungeonRouteCollection?->getPublishedStateName() ?? PublishedState::UNPUBLISHED)
        ->class('form-select') }}
    <small class="form-text text-body-secondary">
        {{ __('view_collection.details.published_state_help') }}
    </small>
    @include('common.forms.form-error', ['key' => 'published_state'])
</div>

@if($teams->isNotEmpty())
    <div class="mb-3{{ $errors->has('team_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_collection.details.team'), 'team_id') }}
        {{ html()->select('team_id', $teamOptions, $dungeonRouteCollection?->team_id)->class('form-select') }}
        <small class="form-text text-body-secondary">
            {{ __('view_collection.details.team_help') }}
        </small>
        @include('common.forms.form-error', ['key' => 'team_id'])
    </div>
@endif

<div class="mb-3{{ $errors->has('dungeon_routes') ? ' has-error' : '' }}">
    {{ html()->label(__('view_collection.details.dungeon_routes'), 'dungeon_routes') }}
    @if($ownDungeonRoutes->isEmpty())
        <p class="text-body-secondary">
            {{ __('view_collection.details.dungeon_routes_none') }}
        </p>
    @else
        <select name="dungeon_routes[]" id="dungeon_routes" class="form-select"
                multiple size="{{ min(15, max(3, $ownDungeonRoutes->count())) }}">
            @foreach($ownDungeonRoutes as $ownDungeonRoute)
                <option value="{{ $ownDungeonRoute->id }}"
                        @if(in_array($ownDungeonRoute->id, $selectedDungeonRouteIds, true)) selected @endif>
                    {{ $ownDungeonRoute->title }} &mdash; {{ __($ownDungeonRoute->dungeon?->name ?? '') }}
                </option>
            @endforeach
        </select>
        <small class="form-text text-body-secondary">
            {{ __('view_collection.details.dungeon_routes_help', ['max' => DungeonRouteCollection::MAX_ROUTES]) }}
        </small>
    @endif
    @include('common.forms.form-error', ['key' => 'dungeon_routes'])
</div>

{{ html()->input('submit')->value($dungeonRouteCollection !== null ? __('view_collection.details.save') : __('view_collection.details.submit'))->class('btn btn-info') }}

{{ html()->closeModelForm() }}

@isset($dungeonRouteCollection)
    {{ html()->form('DELETE', route('collections.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]))->class('mt-4')->open() }}
    {{ html()->input('submit')->value(__('view_collection.details.delete'))->class('btn btn-danger') }}
    {{ html()->closeModelForm() }}
@endisset
