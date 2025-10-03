<?php

use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * @var Spell                   $spell
 * @var Floor                   $floor
 * @var array<string>           $categories
 * @var array<string>           $dispelTypes
 * @var array<string>           $cooldownGroups
 * @var Collection<GameVersion> $allGameVersions
 */

$gameVersionsSelect = $allGameVersions
    ->mapWithKeys(static fn(GameVersion $gameVersion) => [$gameVersion->id => __($gameVersion->name)]);
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$spell ?? null],
    'showAds' => false,
    'title' => isset($spell) ? __('view_admin.spell.edit.title_edit') : __('view_admin.spell.edit.title_new'),
    ])
@section('header-title')
    {{ isset($spell) ? __('view_admin.spell.edit.header_edit') : __('view_admin.spell.edit.header_new') }}
@endsection

@section('content')
    @isset($spell)
        {{ html()->modelForm($spell, 'PATCH', route('admin.spell.update', $spell->id))->attribute('autocomplete', 'off')->acceptsFiles()->open() }}
    @else
        {{ html()->form('POST', route('admin.spell.savenew'))->attribute('autocomplete', 'off')->acceptsFiles()->open() }}
    @endisset
    <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.game_id') . '<span class="form-required">*</span>', 'id') }}
        {{ html()->text('id')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'id'])
    </div>

    <div class="form-group{{ $errors->has('game_version_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.game_version_id'), 'game_version_id') }}
        <span class="form-required">*</span>
        {{ html()->select('game_version_id', $gameVersionsSelect)->class('form-control selectpicker') }}
        @include('common.forms.form-error', ['key' => 'game_version_id'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.name') . '<span class="form-required">*</span>', 'name') }}
        {{ html()->text('name')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('icon_name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.icon_name') . '<span class="form-required">*</span>', 'icon_name') }}
        {{ html()->text('icon_name')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'icon_name'])
    </div>

    <div class="form-group{{ $errors->has('category') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.category') . '<span class="form-required">*</span>', 'category') }}
        {{ html()->select('category', $categories)->class('form-control selectpicker') }}
        @include('common.forms.form-error', ['key' => 'category'])
    </div>

    <div class="form-group{{ $errors->has('dispel_type') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.dispel_type') . '<span class="form-required">*</span>', 'dispel_type') }}
        <?php $dispelTypes = array_merge(['None'], $dispelTypes); ?>
        {{ html()->select('dispel_type', array_combine($dispelTypes, $dispelTypes))->class('form-control selectpicker') }}
        @include('common.forms.form-error', ['key' => 'dispel_type'])
    </div>

    <div class="form-group{{ $errors->has('cooldown_group') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.cooldown_group') . '<span class="form-required">*</span>', 'cooldown_group') }}
        {{ html()->select('cooldown_group', $cooldownGroups)->class('form-control selectpicker') }}
        @include('common.forms.form-error', ['key' => 'cooldown_group'])
    </div>

    <div class="form-group{{ $errors->has('schools') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.schools'), 'schools[]') }}
        {{ html()->multiselect('schools[]', array_flip($schools), isset($spell) ? $spell->getSchoolsAsArray() : null)->class('form-control selectpicker')->attribute('size', count($schools)) }}
        @include('common.forms.form-error', ['key' => 'schools'])
    </div>

    <div class="form-group{{ $errors->has('aura') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.aura'), 'aura') }}
        {{ html()->checkbox('aura', isset($spell) ? $spell->aura : 1, 1)->class('form-control left_checkbox') }}
        @include('common.forms.form-error', ['key' => 'aura'])
    </div>

    <div class="form-group{{ $errors->has('selectable') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.spell.edit.selectable'), 'selectable') }}
        {{ html()->checkbox('selectable', isset($spell) ? $spell->selectable : 1, 1)->class('form-control left_checkbox') }}
        @include('common.forms.form-error', ['key' => 'selectable'])
    </div>

    <div>
        {{ html()->input('submit')->value(__('view_admin.spell.edit.submit'))->class('btn btn-info')->name('submit') }}
        @isset($spell)
            <div class="float-right">
                {{ html()->input('submit')->value(__('view_admin.spell.edit.save_as_new_spell'))->class('btn btn-info')->name('submit') }}
            </div>
        @endisset
    </div>

    {{ html()->closeModelForm() }}
@endsection
