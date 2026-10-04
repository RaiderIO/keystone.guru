<?php

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * The top of a collection's form: its name, description and season. The season comes before the routes because it
 * decides which slots they are picked into. The form element itself is empty; every field names it through its
 * form attribute, so the routes section can sit between these fields and the sharing ones without nesting forms.
 *
 * @var DungeonRouteCollection|null $dungeonRouteCollection
 * @var string                      $formId              Id of the form, which the routes section posts into.
 * @var GameVersion                 $selectedGameVersion
 * @var Collection<int, Season>     $seasons             New collections only.
 * @var Season|null                 $selectedSeason
 * @var string|null                 $prefillName         New collections only.
 * @var string|null                 $prefillDescription  New collections only.
 */

$dungeonRouteCollection ??= null;
$seasons                ??= collect();
$selectedSeason         ??= null;
$prefillName            ??= null;
$prefillDescription     ??= null;

$isNew = $dungeonRouteCollection === null;

/**
 * The help text and, when the field failed validation, its error message - what a field's aria-describedby lists.
 */
$describedBy = static fn(string $key, ?string $helpId = null): string => trim(sprintf(
    '%s %s',
    $helpId ?? '',
    $errors->has($key) ? sprintf('%s_error', $key) : '',
));
?>

@isset($dungeonRouteCollection)
    {{ html()->form('PATCH', route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]))->id($formId)->open() }}
@else
    {{ html()->form('POST', route('collections.savenew'))->id($formId)->open() }}
@endisset
{{ html()->form()->close() }}

<div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.collection.details.name') . '<span class="form-required">*</span>', 'name') }}
    {{ html()->text('name', $dungeonRouteCollection?->name ?? $prefillName)
        ->class('form-control')
        ->attribute('form', $formId)
        ->attribute('maxlength', DungeonRouteCollection::MAX_NAME_LENGTH)
        ->required()
        ->attributeIf($errors->has('name'), 'aria-invalid', 'true')
        ->attributeIf($errors->has('name'), 'aria-describedby', $describedBy('name')) }}
    @include('common.forms.form-error', ['key' => 'name', 'errorId' => 'name_error'])
</div>

<div class="mb-3{{ $errors->has('description') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.collection.details.description'), 'description') }}
    {{ html()->textarea('description', $dungeonRouteCollection?->description ?? $prefillDescription)
        ->class('form-control')
        ->attribute('form', $formId)
        ->rows(3)
        ->attribute('maxlength', 1000)
        ->attributeIf($errors->has('description'), 'aria-invalid', 'true')
        ->attributeIf($errors->has('description'), 'aria-describedby', $describedBy('description')) }}
    @include('common.forms.form-error', ['key' => 'description', 'errorId' => 'description_error'])
</div>

@if($isNew)
    @if($selectedGameVersion->has_seasons)
        <fieldset class="mb-4{{ $errors->has('season_id') ? ' has-error' : '' }}" aria-describedby="season_help">
            <legend class="form-label fs-6">{{ __('view_common.collection.details.season') }}</legend>
            @include('common.collection.seasonradios', [
                'idPrefix' => 'season_id',
                'formId' => $formId,
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
    <fieldset class="mb-4{{ $errors->has('season_id') ? ' has-error' : '' }}" aria-describedby="season_help">
        <legend class="form-label fs-6">{{ __('view_common.collection.details.season') }}</legend>
        @include('common.collection.seasonradios', [
            'idPrefix' => 'season_id',
            'formId' => $formId,
            'seasons' => collect([$selectedSeason]),
            'selectedSeasonId' => $selectedSeason->id,
        ])
        <small id="season_help" class="form-text text-body-secondary d-block">
            {{ __('view_common.collection.details.season_make_free_form') }}
        </small>
        @include('common.forms.form-error', ['key' => 'season_id'])
    </fieldset>
@endif
