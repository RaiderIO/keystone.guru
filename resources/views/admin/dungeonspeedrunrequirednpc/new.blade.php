<?php
/** @var $dungeon \App\Models\Dungeon */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon],
    'showAds' => false,
    'title' => sprintf(
        __('views/admin.dungeonspeedrunrequirednpc.new.title'),
        __($dungeon->name)
    )]
)
@section('header-title')
    {{ __('views/admin.dungeonspeedrunrequirednpc.new.header', ['dungeon' => __($dungeon->name)]) }}
@endsection
<?php
/**
 */
?>

@section('content')
    {{ Form::open(['route' => ['admin.dungeonspeedrunrequirednpc.savenew', 'dungeon' => $dungeon->slug]]) }}

    {!! Form::hidden('dungeon_id', $dungeon->id) !!}

    <div class="form-group{{ $errors->has('npc_id') ? ' has-error' : '' }}">
        {!! Form::label('npc_id', __('views/admin.dungeonspeedrunrequirednpc.new.npc_id'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::select('npc_id', $npcIds, null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true']) !!}
        @include('common.forms.form-error', ['key' => 'npc_id'])
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
