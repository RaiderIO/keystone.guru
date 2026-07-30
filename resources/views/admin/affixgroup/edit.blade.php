<?php

use App\Http\Requests\AffixGroupFormRequest;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var Season                                          $season
 * @var AffixGroup|null                                 $affixGroup
 * @var Collection<int, string>                         $affixSelect
 * @var array<int, array{affix_id: int, key_level: int}> $couplings
 */
$affixSelectWithNone = collect([-1 => __('view_admin.affixgroup.edit.no_affix')])->union($affixSelect);
?>

@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$season, $affixGroup ?? null],
    'showAds' => false,
    'title' => isset($affixGroup) ? __('view_admin.affixgroup.edit.title_edit') : __('view_admin.affixgroup.edit.title_new'),
    ])

@section('header-title')
    {{ isset($affixGroup)
        ? __('view_admin.affixgroup.edit.header_edit', ['season' => __($season->name_long)])
        : __('view_admin.affixgroup.edit.header_new', ['season' => __($season->name_long)]) }}
@endsection

@section('content')
    @isset($affixGroup)
        {{ html()->form('PATCH', route('admin.affixgroup.update', ['season' => $season, 'affixGroup' => $affixGroup]))->open() }}
    @else
        {{ html()->form('POST', route('admin.affixgroup.savenew', ['season' => $season]))->open() }}
    @endisset

    <div class="mb-3{{ $errors->has('seasonal_index') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.affixgroup.edit.seasonal_index'), 'seasonal_index') }}
        {{ html()->number('seasonal_index', old('seasonal_index', $affixGroup->seasonal_index ?? null))->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'seasonal_index'])
    </div>

    <div class="mb-3{{ $errors->has('confirmed') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.affixgroup.edit.confirmed'), 'confirmed') }}
        {{ html()->checkbox('confirmed', old('confirmed', $affixGroup->confirmed ?? false), 1)->class('form-check-input') }}
        @include('common.forms.form-error', ['key' => 'confirmed'])
    </div>

    <div class="mb-3{{ $errors->has('affix_id_1') || $errors->has('key_level_1') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.affixgroup.edit.affix'), 'affix_id_1')->class('fw-bold') }}
        <span class="form-required">*</span>
        <div class="row">
            <div class="col">
                {{ html()->select('affix_id_1', $affixSelect, old('affix_id_1', $couplings[0]['affix_id'] ?? null))->class('form-control selectpicker')->data('live-search', 'true') }}
                @include('common.forms.form-error', ['key' => 'affix_id_1'])
            </div>
            <div class="col-auto">
                {{ html()->number('key_level_1', old('key_level_1', $couplings[0]['key_level'] ?? null))->class('form-control')->placeholder(__('view_admin.affixgroup.edit.key_level')) }}
                @include('common.forms.form-error', ['key' => 'key_level_1'])
            </div>
        </div>
    </div>

    @for ($slot = 2; $slot <= AffixGroupFormRequest::SLOT_COUNT; $slot++)
        <div class="mb-3{{ $errors->has('affix_id_' . $slot) || $errors->has('key_level_' . $slot) ? ' has-error' : '' }}">
            <div class="row">
                <div class="col">
                    {{ html()->select('affix_id_' . $slot, $affixSelectWithNone, old('affix_id_' . $slot, $couplings[$slot - 1]['affix_id'] ?? -1))->class('form-control selectpicker')->data('live-search', 'true') }}
                    @include('common.forms.form-error', ['key' => 'affix_id_' . $slot])
                </div>
                <div class="col-auto">
                    {{ html()->number('key_level_' . $slot, old('key_level_' . $slot, $couplings[$slot - 1]['key_level'] ?? null))->class('form-control')->placeholder(__('view_admin.affixgroup.edit.key_level')) }}
                    @include('common.forms.form-error', ['key' => 'key_level_' . $slot])
                </div>
            </div>
        </div>
    @endfor

    {{ html()->input('submit')->value(__('view_admin.affixgroup.edit.submit'))->class('btn btn-info') }}

    {{ html()->form()->close() }}
@endsection
