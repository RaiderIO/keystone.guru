<?php

use App\Http\Requests\DungeonRoute\DungeonRouteCollectionCreateFormRequest;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\PublishedState;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * The bottom of a collection's form: who it is for and who may see it, the button saving everything the form holds
 * and, for an existing collection, deleting it - kept apart from the save button. Rendered below the routes section,
 * after common.collection.details opened the form.
 *
 * @var DungeonRouteCollection|null                     $dungeonRouteCollection
 * @var string                                          $formId
 * @var Collection<int, Team>                           $teams
 * @var Collection<int, DungeonRouteCollectionCategory> $categories
 * @var bool                                            $mayCreateCollection New collections only; false at the collection cap.
 * @var array<string, string>                           $formUrlParams       New collections only; the query the routes section is rebuilt with.
 * @var bool                                            $selectName          Select the name on load, for a fresh duplicate.
 */

$dungeonRouteCollection ??= null;
$teams                  ??= collect();
$categories             ??= collect();
$mayCreateCollection    ??= true;
$formUrlParams          ??= [];
$selectName             ??= false;

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

/**
 * The help text and, when the field failed validation, its error message - what a field's aria-describedby lists.
 */
$describedBy = static fn(string $key, ?string $helpId = null): string => trim(sprintf(
    '%s %s',
    $helpId ?? '',
    $errors->has($key) ? sprintf('%s_error', $key) : '',
));

$selectedPublishedState = old('published_state', $dungeonRouteCollection?->getPublishedStateName() ?? PublishedState::UNPUBLISHED);
?>

<section class="mb-4" aria-labelledby="collection_sharing_heading">
    <h2 id="collection_sharing_heading" class="h4">{{ __('view_common.collection.details.sharing') }}</h2>

    <div class="mb-3{{ $errors->has('category_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_common.collection.details.category'), 'category_id') }}
        {{ html()->select('category_id', $categoryOptions, $dungeonRouteCollection?->dungeon_route_collection_category_id)
            ->class('form-select')
            ->attribute('form', $formId)
            ->attribute('aria-describedby', $describedBy('category_id', 'category_id_help')) }}
        <small id="category_id_help" class="form-text text-body-secondary">
            {{ __('view_common.collection.details.category_help') }}
        </small>
        @include('common.forms.form-error', ['key' => 'category_id', 'errorId' => 'category_id_error'])
    </div>

    <div class="mb-3{{ $errors->has('published_state') ? ' has-error' : '' }}">
        {{ html()->label(__('view_common.collection.details.published_state'), 'published_state') }}
        @include('common.forms.publishedstate', [
            'id' => 'published_state',
            'name' => 'published_state',
            'formId' => $formId,
            'publishedStates' => DungeonRouteCollection::AVAILABLE_PUBLISHED_STATES,
            'availablePublishedStates' => $availablePublishedStates,
            'selected' => $selectedPublishedState,
            'subtexts' => collect(DungeonRouteCollection::AVAILABLE_PUBLISHED_STATES)->mapWithKeys(static fn(string $publishedState): array => [
                $publishedState => __(sprintf('view_collection.published_state_subtext.%s', $publishedState)),
            ])->all(),
        ])
        <small id="published_state_help" class="form-text text-body-secondary">
            {{ __('view_common.collection.details.published_state_help') }}
        </small>
        @include('common.forms.form-error', ['key' => 'published_state', 'errorId' => 'published_state_error'])
    </div>

    @if($teams->isNotEmpty())
        {{-- Only a collection visible to a team has a team to pick --}}
        <div id="collection_team_field" class="mb-3{{ $errors->has('team_id') ? ' has-error' : '' }}"
             @if($selectedPublishedState !== PublishedState::TEAM) hidden @endif>
            {{ html()->label(__('view_common.collection.details.team'), 'team_id') }}
            {{ html()->select('team_id', $teamOptions, $dungeonRouteCollection?->team_id)
                ->class('form-select')
                ->attribute('form', $formId)
                ->attribute('aria-describedby', $describedBy('team_id', 'team_id_help')) }}
            <small id="team_id_help" class="form-text text-body-secondary">
                {{ __('view_common.collection.details.team_help') }}
            </small>
            @include('common.forms.form-error', ['key' => 'team_id', 'errorId' => 'team_id_error'])
        </div>
    @endif
</section>

<div id="collection_save" class="d-flex flex-wrap align-items-center gap-3 mb-5">
    @if($isNew && !$mayCreateCollection)
        {{ html()->input('submit')
            ->value(__('view_common.collection.details.submit'))
            ->class('btn btn-primary')
            ->attribute('form', $formId)
            ->disabled()
            ->attribute('aria-describedby', 'collection_max_collections') }}
        <p id="collection_max_collections" class="text-warning mb-0">
            {{ __('view_collection.index.max_collections', ['max' => DungeonRouteCollection::MAX_COLLECTIONS]) }}
        </p>
    @elseif($isNew)
        {{ html()->input('submit')
            ->value(__('view_common.collection.details.submit'))
            ->class('btn btn-primary')
            ->attribute('form', $formId) }}
    @else
        {{ html()->input('submit')
            ->value(__('view_common.collection.details.save'))
            ->class('btn btn-primary')
            ->attribute('form', $formId)
            ->attribute('aria-describedby', 'collection_save_help') }}
        <small id="collection_save_help" class="text-body-secondary">
            {{ __('view_common.collection.details.save_help') }}
        </small>
    @endif
</div>

@isset($dungeonRouteCollection)
    <section id="collection_danger_zone" class="collection_danger_zone p-3 mb-4" aria-labelledby="collection_danger_zone_heading">
        <h2 id="collection_danger_zone_heading" class="h5">{{ __('view_common.collection.details.delete_heading') }}</h2>
        <p class="text-body-secondary">{{ __('view_common.collection.details.delete_help') }}</p>
        {{ html()->form('DELETE', route('collections.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]))->id($deleteFormId)->open() }}
        {{ html()->input('submit')
            ->value(__('view_common.collection.details.delete'))
            ->class('btn btn-outline-danger') }}
        {{ html()->form()->close() }}
    </section>
@endisset

@include('common.general.inline', ['path' => 'common/collection/details', 'options' => [
    'formSelector' => sprintf('#%s', $formId),
    'nameSelector' => '#name',
    'selectName' => $selectName,
    'dungeonRoutesSelector' => '#collection_routes',
    'loadingSelector' => '#collection_routes_loading',
    'errorSelector' => '#collection_routes_error',
    'seasonSelector' => 'input[name="season_id"]',
    // Only a new collection's season can be switched to another one
    'formUrl' => $isNew ? route('collections.new') : null,
    'seasonNone' => DungeonRouteCollectionCreateFormRequest::SEASON_NONE,
    'formUrlParams' => $formUrlParams,
    // Saving a season set as free-form is the one change that cannot be taken back
    'confirmFreeForm' => !$isNew && $dungeonRouteCollection->isSeasonSet(),
    'publishedStateSelector' => '#published_state',
    'teamPublishedState' => PublishedState::TEAM,
    'teamFieldSelector' => '#collection_team_field',
    'deleteFormSelector' => $isNew ? null : sprintf('#%s', $deleteFormId),
]])
