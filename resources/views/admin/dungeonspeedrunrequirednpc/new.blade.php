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
    {{ Form::open(['route' => ['admin.dungeonspeedrunrequirednpc.savenew', 'dungeon' => $dungeon, 'floor' => $floor, 'difficulty' => $difficulty]]) }}

    {!! Form::hidden('dungeon_id', $dungeon->id) !!}
    {!! Form::hidden('floor_id', $floor->id) !!}
    {!! Form::hidden('difficulty', $difficulty) !!}

    <div class="form-group{{ $errors->has('npc_id') ? ' has-error' : '' }}">
        {!! Form::label('npc_id', __('view_admin.dungeonspeedrunrequirednpc.new.npc_id'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::select('npc_id', $npcIds, null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true']) !!}
        @include('common.forms.form-error', ['key' => 'npc_id'])
    </div>

    <div class="form-group{{ $errors->has('npc2_id') ? ' has-error' : '' }}">
        {!! Form::label('npc2_id', __('view_admin.dungeonspeedrunrequirednpc.new.linked_npc_ids'), ['class' => 'font-weight-bold']) !!}
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
        {!! Form::label('count', __('view_admin.dungeonspeedrunrequirednpc.new.count'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('count', 0, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'count'])
    </div>

    {!! Form::submit(__('view_admin.dungeonspeedrunrequirednpc.new.submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
