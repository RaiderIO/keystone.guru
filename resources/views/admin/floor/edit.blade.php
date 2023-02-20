<?php
/** @var $dungeon \App\Models\Dungeon */
/* @var $floor \App\Models\Floor */
/* @var $floorCouplings \App\Models\FloorCoupling[]|\Illuminate\Support\Collection */
$floor = $floor ?? null;
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon, $floor ?? null],
    'showAds' => false,
    'title' => sprintf(
        isset($floor) ? __('views/admin.floor.edit.title_edit') : __('views/admin.floor.edit.title_new'),
        __($dungeon->name)
    )]
)
@section('header-title')
    {{ sprintf(isset($floor) ? __('views/admin.floor.edit.header_edit') : __('views/admin.floor.edit.header_new'), __($dungeon->name)) }}
@endsection
<?php
/**
 */
?>

@section('content')
    @isset($floor)
        {{ Form::model($floor, ['route' => ['admin.floor.update', 'dungeon' => $dungeon->slug, 'floor' => $floor->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => ['admin.floor.savenew', 'dungeon' => $dungeon->slug]]) }}
    @endisset

    <div class="form-group{{ $errors->has('index') ? ' has-error' : '' }}">
        {!! Form::label('index', __('views/admin.floor.edit.index'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('index', optional($floor)->index ?? $dungeon->floors()->count() + 1, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'index'])
    </div>

    <div class="form-group{{ $errors->has('mdt_sub_level') ? ' has-error' : '' }}">
        {!! Form::label('mdt_sub_level', __('views/admin.floor.edit.mdt_sub_level'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('mdt_sub_level', optional($floor)->mdt_sub_level, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'mdt_sub_level'])
    </div>

    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('views/admin.floor.edit.floor_name'), ['class' => 'font-weight-bold']) !!}
        <span class="form-required">*</span>
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'name'])
    </div>

    <div class="form-group{{ $errors->has('min_enemy_size') ? ' has-error' : '' }}">
        {!! Form::label('min_enemy_size', sprintf(__('views/admin.floor.edit.min_enemy_size'), config('keystoneguru.min_enemy_size_default')), ['class' => 'font-weight-bold']) !!}
        {!! Form::number('min_enemy_size', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'min_enemy_size'])
    </div>

    <div class="form-group{{ $errors->has('max_enemy_size') ? ' has-error' : '' }}">
        {!! Form::label('max_enemy_size', sprintf(__('views/admin.floor.edit.max_enemy_size'), config('keystoneguru.max_enemy_size_default')), ['class' => 'font-weight-bold']) !!}
        {!! Form::number('max_enemy_size', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'max_enemy_size'])
    </div>

    <div class="form-group{{ $errors->has('percentage_display_zoom') ? ' has-error' : '' }}">
        {!! Form::label('percentage_display_zoom', __('views/admin.floor.edit.percentage_display_zoom'), ['class' => 'font-weight-bold']) !!}
        {!! Form::number('percentage_display_zoom', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'percentage_display_zoom'])
    </div>

    <div class="form-group{{ $errors->has('default') ? ' has-error' : '' }}">
        {!! Form::label('default', __('views/admin.floor.edit.default'), ['class' => 'font-weight-bold']) !!}
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('views/admin.floor.edit.default_title')
                 }}"></i>
        {!! Form::checkbox('default', 1, optional($floor)->default ?? (int)($dungeon->floors()->count() === 0), ['class' => 'form-control left_checkbox']) !!}
        @include('common.forms.form-error', ['key' => 'default'])
    </div>

    <div class="form-group">
        @include('admin.floor.connectedfloors', ['floor' => $floor])
    </div>

    {!! Form::submit(__('views/admin.floor.edit.submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}

    @isset($floor)
        <div class="form-group">
            @include('admin.floor.speedrunrequirednpcs', ['difficulty' => \App\Models\Dungeon::DIFFICULTY_10_MAN, 'floor' => $floor])
        </div>
        <div class="form-group">
            @include('admin.floor.speedrunrequirednpcs', ['difficulty' => \App\Models\Dungeon::DIFFICULTY_25_MAN, 'floor' => $floor])
        </div>
    @endisset
@endsection
