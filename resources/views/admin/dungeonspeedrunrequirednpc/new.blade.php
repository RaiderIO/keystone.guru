<?php
/** @var $dungeon \App\Models\Dungeon */
/** @var $floor \App\Models\Floor */
/** @var $npcIds array */
/** @var $npcIdsWithNullable array */
/** @var $mode int */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon, $floor, $mode],
    'showAds' => false,
    'title' => sprintf(
        \App\Models\Speedrun\DungeonSpeedrunRequiredNpc::MODE_10_MAN ?
        __('views/admin.dungeonspeedrunrequirednpc.new.title_10_man') :
        __('views/admin.dungeonspeedrunrequirednpc.new.title_25_man')
        ,
        __($dungeon->name)
    )]
)
@section('header-title')
    @if($mode === \App\Models\Speedrun\DungeonSpeedrunRequiredNpc::MODE_10_MAN )
            {{ __('views/admin.dungeonspeedrunrequirednpc.new.header_10_man', ['dungeon' => __($dungeon->name)]) }}
        @else
            {{ __('views/admin.dungeonspeedrunrequirednpc.new.header_25_man', ['dungeon' => __($dungeon->name)]) }}
    @endif
@endsection

@section('content')
    {{ Form::open(['route' => ['admin.dungeonspeedrunrequirednpc.savenew', 'dungeon' => $dungeon, 'floor' => $floor, 'mode' => $mode]]) }}

    {!! Form::hidden('dungeon_id', $dungeon->id) !!}
    {!! Form::hidden('floor_id', $floor->id) !!}
    {!! Form::hidden('mode', $mode) !!}

    <div class="form-group{{ $errors->has('npc_id') ? ' has-error' : '' }}">
        {!! Form::label('npc_id', __('views/admin.dungeonspeedrunrequirednpc.new.npc_id'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::select('npc_id', $npcIds, null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true']) !!}
        @include('common.forms.form-error', ['key' => 'npc_id'])
    </div>

    <div class="form-group{{ $errors->has('npc2_id') ? ' has-error' : '' }}">
        {!! Form::label('npc2_id', __('views/admin.dungeonspeedrunrequirednpc.new.linked_npc_ids'), ['class' => 'font-weight-bold']) !!}
        {!! Form::select('npc2_id', $npcIdsWithNullable, null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true']) !!}
        @include('common.forms.form-error', ['key' => 'npc2_id'])
    </div>

    <div class="form-group{{ $errors->has('npc3_id') ? ' has-error' : '' }}">
        {!! Form::select('npc3_id', $npcIdsWithNullable, null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true']) !!}
        @include('common.forms.form-error', ['key' => 'npc3_id'])
    </div>

    <div class="form-group{{ $errors->has('npc4_id') ? ' has-error' : '' }}">
        {!! Form::select('npc4_id', $npcIdsWithNullable, null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true']) !!}
        @include('common.forms.form-error', ['key' => 'npc4_id'])
    </div>

    <div class="form-group{{ $errors->has('npc5_id') ? ' has-error' : '' }}">
        {!! Form::select('npc5_id', $npcIdsWithNullable, null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true']) !!}
        @include('common.forms.form-error', ['key' => 'npc5_id'])
    </div>

    <div class="form-group{{ $errors->has('count') ? ' has-error' : '' }}">
        {!! Form::label('count', __('views/admin.dungeonspeedrunrequirednpc.new.count'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('count', 0, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'count'])
    </div>

    {!! Form::submit(__('views/admin.dungeonspeedrunrequirednpc.new.submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
