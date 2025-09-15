<?php
use App\Models\Dungeon;
use App\Models\Floor\Floor;

/**
 * @var Dungeon $dungeon
 * @var Floor $floor
 * @var array $npcIds
 * @var array $npcIdsWithNullable
 * @var int $difficulty
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon, $floor, $difficulty],
    'showAds' => false,
    'title' => sprintf(
        $difficulty === Dungeon::DIFFICULTY_10_MAN ?
        __('view_admin.dungeonspeedrunrequirednpc.new.title_10_man') :
        __('view_admin.dungeonspeedrunrequirednpc.new.title_25_man')
        ,
        __($dungeon->name)
    )]
)
@section('header-title')
    @if($difficulty === Dungeon::DIFFICULTY_10_MAN )
        {{ __('view_admin.dungeonspeedrunrequirednpc.new.header_10_man', ['dungeon' => __($dungeon->name)]) }}
    @else
        {{ __('view_admin.dungeonspeedrunrequirednpc.new.header_25_man', ['dungeon' => __($dungeon->name)]) }}
    @endif
@endsection

@section('content')
    {{ html()->form('POST', route('admin.dungeonspeedrunrequirednpc.savenew', ['dungeon' => $dungeon, 'floor' => $floor, 'difficulty' => $difficulty]))->open() }}

    {{ html()->hidden('dungeon_id', $dungeon->id) }}
    {{ html()->hidden('floor_id', $floor->id) }}
    {{ html()->hidden('difficulty', $difficulty) }}

    <div class="form-group{{ $errors->has('npc_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.dungeonspeedrunrequirednpc.new.npc_id'), 'npc_id')->class('font-weight-bold') }}
        <span class="form-required">*</span>
        {{ html()->select('npc_id', $npcIds)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc_id'])
    </div>

    <div class="form-group{{ $errors->has('npc2_id') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.dungeonspeedrunrequirednpc.new.linked_npc_ids'), 'npc2_id')->class('font-weight-bold') }}
        {{ html()->select('npc2_id', $npcIdsWithNullable)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc2_id'])
    </div>

    <div class="form-group{{ $errors->has('npc3_id') ? ' has-error' : '' }}">
        {{ html()->select('npc3_id', $npcIdsWithNullable)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc3_id'])
    </div>

    <div class="form-group{{ $errors->has('npc4_id') ? ' has-error' : '' }}">
        {{ html()->select('npc4_id', $npcIdsWithNullable)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc4_id'])
    </div>

    <div class="form-group{{ $errors->has('npc5_id') ? ' has-error' : '' }}">
        {{ html()->select('npc5_id', $npcIdsWithNullable)->class('form-control selectpicker')->data('live-search', 'true') }}
        @include('common.forms.form-error', ['key' => 'npc5_id'])
    </div>

    <div class="form-group{{ $errors->has('count') ? ' has-error' : '' }}">
        {{ html()->label(__('view_admin.dungeonspeedrunrequirednpc.new.count'), 'count')->class('font-weight-bold') }}
        <span class="form-required">*</span>
        {{ html()->text('count', 0)->class('form-control') }}
        @include('common.forms.form-error', ['key' => 'count'])
    </div>

    {{ html()->input('submit')->value(__('view_admin.dungeonspeedrunrequirednpc.new.submit'))->class('btn btn-info') }}

    {{ html()->form()->close() }}
@endsection
