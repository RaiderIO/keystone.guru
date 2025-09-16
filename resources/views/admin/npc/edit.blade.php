<?php

use App\Models\Floor\Floor;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClass;
use App\Models\Npc\NpcType;
use App\Models\Spell\Spell;

/**
 * @var Npc $npc
 * @var Floor $floor
 * @var array $classifications
 * @var Spell[] $spells
 * @var array $bolsteringNpcs
 */
?>

@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$npc ?? null],
    'showAds' => false,
    'title' => isset($npc) ? __('view_admin.npc.edit.title_edit', ['name' => __($npc->name)]) : __('view_admin.npc.edit.title_new'),
])

@section('header-title')
    {{ isset($npc) ? __('view_admin.npc.edit.header_edit', ['name' => __($npc->name)]) : __('view_admin.npc.edit.header_new') }}
@endsection
@section('content')
    @isset($npc)
        {{ html()->modelForm($npc, 'PATCH', route('admin.npc.update', $npc->id))->attribute('autocomplete', 'off')->acceptsFiles()->open() }}
    @else
        {{ html()->form('POST', route('admin.npc.savenew'))->attribute('autocomplete', 'off')->acceptsFiles()->open() }}
    @endisset

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npc.edit.name'), 'name') }}
        <span class="form-required">*</span>
        {{ html()->text('name')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npc.edit.game_id'), 'id') }}
        <span class="form-required">*</span>
        {{ html()->text('id')->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'id'])
    </div>

    @include('common.dungeon.select', [
        'name' => 'dungeon_ids[]',
        'selected' => isset($npc) ? $npc->dungeons->pluck('id')->toArray() : [],
        'multiple' => true,
        'showAll' => false,
        'activeOnly' => false,
        'ignoreGameVersion' => true
    ])

    <div class="form-group{{ $errors->has('classification_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npc.edit.classification'), 'classification_id') }}
        <span class="form-required">*</span>
        {{ html()->select('classification_id', $classifications)->class('form-control selectpicker') }}
        @include('common.forms.form-error', ['key' => 'classification_id'])
    </div>

    <div class="form-group{{ $errors->has('aggressiveness') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npc.edit.aggressiveness'), 'aggressiveness') }}
        <span class="form-required">*</span>
        <?php
        $aggressivenessSelect = [];
        foreach (Npc::ALL_AGGRESSIVENESS as $aggressiveness) {
            $aggressivenessSelect[$aggressiveness] = __(sprintf('npcaggressiveness.%s', $aggressiveness));
        }
        ?>
        {{ html()->select('aggressiveness', $aggressivenessSelect)->class('form-control selectpicker') }}
        @include('common.forms.form-error', ['key' => 'aggressiveness'])
    </div>

    <div class="form-group{{ $errors->has('npc_type_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npc.edit.type'), 'npc_class_id') }}
        <span class="form-required">*</span>
        {{ html()->select('npc_type_id', NpcType::pluck('type', 'id'))->class('form-control selectpicker') }}
        @include('common.forms.form-error', ['key' => 'npc_type_id'])
    </div>

    <div class="form-group{{ $errors->has('npc_class_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npc.edit.class'), 'npc_class_id') }}
        <span class="form-required">*</span>
        {{ html()->select('npc_class_id', NpcClass::pluck('name', 'id')->mapWithKeys(static fn($name, $id) => [$id => __($name)]))->class('form-control selectpicker') }}
        @include('common.forms.form-error', ['key' => 'npc_class_id'])
    </div>

    <div class="form-group{{ $errors->has('level') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.npc.edit.level'), 'level') }}
        {{ html()->number('level', $npc->level ?? null)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'level'])
    </div>

    <div class="form-group">
        <div class="row">
            <div class="col">
                <div class="{{ $errors->has('dangerous') ? ' has-error' : '' }}">
                    {{ html()->label(__('view_admin.npc.edit.dangerous'), 'dangerous') }}
                    {{ html()->checkbox('dangerous', isset($npc) ? $npc->dangerous : 0, 1)->class('form-control left_checkbox') }}
                    @include('common.forms.form-error', ['key' => 'dangerous'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('truesight') ? ' has-error' : '' }}">
                    {{ html()->label(__('view_admin.npc.edit.truesight'), 'truesight') }}
                    {{ html()->checkbox('truesight', isset($npc) ? $npc->truesight : 0, 1)->class('form-control left_checkbox') }}
                    @include('common.forms.form-error', ['key' => 'truesight'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('bursting') ? ' has-error' : '' }}">
                    {{ html()->label(__('view_admin.npc.edit.bursting'), 'bursting') }}
                    {{ html()->checkbox('bursting', isset($npc) ? $npc->bursting : 1, 1)->class('form-control left_checkbox') }}
                    @include('common.forms.form-error', ['key' => 'bursting'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('bolstering') ? ' has-error' : '' }}">
                    {{ html()->label(__('view_admin.npc.edit.bolstering'), 'bolstering') }}
                    {{ html()->checkbox('bolstering', isset($npc) ? $npc->bolstering : 1, 1)->class('form-control left_checkbox') }}
                    @include('common.forms.form-error', ['key' => 'bolstering'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('sanguine') ? ' has-error' : '' }}">
                    {{ html()->label(__('view_admin.npc.edit.sanguine'), 'sanguine') }}
                    {{ html()->checkbox('sanguine', isset($npc) ? $npc->sanguine : 1, 1)->class('form-control left_checkbox') }}
                    @include('common.forms.form-error', ['key' => 'sanguine'])
                </div>
            </div>
            <div class="col">
                <div class="{{ $errors->has('runs_away_in_fear') ? ' has-error' : '' }}">
                    {{ html()->label(__('view_admin.npc.edit.runs_away_in_fear'), 'runs_away_in_fear') }}
                    {{ html()->checkbox('runs_away_in_fear', isset($npc) ? $npc->runs_away_in_fear : 0, 1)->class('form-control left_checkbox') }}
                    @include('common.forms.form-error', ['key' => 'runs_away_in_fear'])
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        {{ html()->label(__('view_admin.npc.edit.bolstering_npc_whitelist'), 'bolstering_whitelist_npcs[]') }}
        {{ html()->multiselect('bolstering_whitelist_npcs[]', $bolsteringNpcs, isset($npc) ? $npc->npcbolsteringwhitelists->pluck(['whitelist_npc_id'])->toArray() : [])->class('form-control selectpicker')->data('live-search', 'true')->data('selected-text-format', 'count > 1')->data('count-selected-text', __('view_admin.npc.edit.bolstering_npc_whitelist_count')) }}
    </div>

    <div class="form-group">
        {{ html()->label(__('view_admin.npc.edit.spells'), 'spells[]') }}
        @php($selectedSpells = isset($npc) ? $npc->spells(false)->get()->pluck(['id'])->toArray() : [])
        <!--suppress HtmlFormInputWithoutLabel -->
        <!--selectpicker-->
        <select class="form-control" name="spells[]" multiple="multiple"
                data-live-search="true" data-selected-text-format="count > 1"
                data-count-selected-text="{{ __('view_admin.npc.edit.spells_count') }}">
            @foreach($spells as $spell)
                <option value="{{$spell->id}}" {{in_array($spell->id, $selectedSpells) ? 'selected="selected"' : ''}}
                data-content="<span><img src='{{$spell->icon_url}}' width='24px'/> {{__($spell->name)}} ({{$spell->id}}) </span>"
                >
                </option>
            @endforeach
        </select>
    </div>



    <div class="form-group">
        {{ html()->input('submit')->value(__('view_admin.npc.edit.submit'))->class('btn btn-info')->name('submit')->value('submit') }}
        @isset($npc)
            <div class="float-right">
                {{ html()->input('submit')->value(__('view_admin.npc.edit.save_as_new_npc'))->class('btn btn-info')->name('submit')->value('saveasnew') }}
            </div>
        @endisset
    </div>

    {{ html()->closeModelForm() }}

    @isset($npc)
        <div class="form-group">
            @include('admin.npc.npchealth', ['npc' => $npc])
        </div>

        <div class="form-group">
            @include('admin.npc.npcenemyforces', ['npc' => $npc])
        </div>
    @endisset
@endsection
