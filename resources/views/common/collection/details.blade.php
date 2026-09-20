<?php

use App\Http\Requests\DungeonRoute\DungeonRouteCollectionCreateFormRequest;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection|null                            $dungeonRouteCollection
 * @var string                                                 $formId    Id of the form, which the routes section posts into.
 * @var GameVersion                                            $selectedGameVersion
 * @var Collection<int, Season>                                $seasons               New collections only.
 * @var Season|null                                            $selectedSeason
 * @var Collection<int, Team>                                  $teams
 * @var Collection<int, DungeonRouteCollectionCategory>        $categories
 */

$dungeonRouteCollection ??= null;
$seasons                ??= collect();
$selectedSeason         ??= null;
$teams                  ??= collect();
$categories             ??= collect();

$isNew        = $dungeonRouteCollection === null;
$deleteFormId = 'collection_delete_form';

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
    {{ html()->modelForm($dungeonRouteCollection, 'PATCH', route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]))->id($formId)->open() }}
@else
    {{ html()->form('POST', route('collections.savenew'))->id($formId)->open() }}
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

@if($isNew)
    @if($selectedGameVersion->has_seasons)
        <fieldset class="mb-3{{ $errors->has('season_id') ? ' has-error' : '' }}" aria-describedby="season_help">
            <legend class="form-label fs-6">{{ __('view_common.collection.details.season') }}</legend>
            @include('common.collection.seasonradios', [
                'idPrefix' => 'season_id',
                'seasons' => $seasons,
                'selectedSeasonId' => $selectedSeason?->id,
            ])
            <small id="season_help" class="form-text text-body-secondary d-block">
                {{ __('view_common.collection.details.season_help') }}
            </small>
            @include('common.forms.form-error', ['key' => 'season_id'])
        </fieldset>
    @endif
@elseif($dungeonRouteCollection->isSeasonSet() && $selectedSeason !== null)
    <fieldset class="mb-3{{ $errors->has('season_id') ? ' has-error' : '' }}" aria-describedby="season_help">
        <legend class="form-label fs-6">{{ __('view_common.collection.details.season') }}</legend>
        @include('common.collection.seasonradios', [
            'idPrefix' => 'season_id',
            'seasons' => collect([$selectedSeason]),
            'selectedSeasonId' => $selectedSeason->id,
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

@if($isNew)
@include('common.general.inline', ['path' => 'common/collection/details', 'options' => [
    'dungeonRoutesSelector' => '#collection_routes',
    'loadingSelector' => '#collection_routes_loading',
    'errorSelector' => '#collection_routes_error',
    'seasonSelector' => 'input[name="season_id"]',
    'formUrl' => route('collections.new'),
    'seasonNone' => DungeonRouteCollectionCreateFormRequest::SEASON_NONE,
]])
@endif

{{ html()->closeModelForm() }}

@isset($dungeonRouteCollection)
    {{ html()->form('DELETE', route('collections.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]))->id($deleteFormId)->open() }}
    {{ html()->closeModelForm() }}
@endisset

{{-- Both buttons sit outside the form they submit, so saving and deleting share one row --}}
<div class="d-flex align-items-center">
    {{ html()->input('submit')
        ->value($dungeonRouteCollection !== null ? __('view_common.collection.details.save') : __('view_common.collection.details.submit'))
        ->class('btn btn-info')
        ->attribute('form', $formId) }}
    @isset($dungeonRouteCollection)
        {{ html()->input('submit')
            ->value(__('view_common.collection.details.delete'))
            ->class('btn btn-danger ms-auto')
            ->attribute('form', $deleteFormId) }}
    @endisset
</div>
