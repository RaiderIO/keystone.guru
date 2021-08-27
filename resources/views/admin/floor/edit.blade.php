<?php
/** @var $dungeon \App\Models\Dungeon */
/* @var $floor \App\Models\Floor */
/* @var $floorCouplings \App\Models\FloorCoupling[]|\Illuminate\Support\Collection */
$connectedFloorCandidates = $dungeon->floors;
if (isset($floor)) {
    $connectedFloorCandidates = $connectedFloorCandidates->except(optional($floor)->id);
}
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon, $floor ?? null],
    'showAds' => false,
    'title' => sprintf(
        $npc === null ? __('views/admin.floor.edit.title_new') : __('views/admin.floor.edit.title_edit'),
        $dungeon->name
    )]
)])
@section('header-title')
    {{ sprintf($npc === null ? __('views/admin.floor.edit.header_new') : __('views/admin.floor.edit.header_edit'), $dungeon->name) }}
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
        {!! Form::text('index', null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'index'])
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

    <div class="form-group{{ $errors->has('default') ? ' has-error' : '' }}">
        {!! Form::label('default', __('views/admin.floor.edit.default'), ['class' => 'font-weight-bold']) !!}
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
                __('views/admin.floor.edit.default_title')
                 }}"></i>
        {!! Form::checkbox('default', 1, isset($floor) ? $floor->default : 1, ['class' => 'form-control left_checkbox']) !!}
        @include('common.forms.form-error', ['key' => 'default'])
    </div>

    @if($connectedFloorCandidates->isNotEmpty())
        {!! Form::label('connectedfloors[]', __('views/admin.floor.edit.connected_floors'), ['class' => 'font-weight-bold']) !!}
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
            __('views/admin.floor.edit.connected_floors_title')
             }}"></i>

        <div class="row mb-4">
            <div class="col-2">
                {{ __('views/admin.floor.edit.connected') }}
            </div>
            <div class="col-8">
                {{ __('views/admin.floor.edit.floor_name') }}
            </div>
            <div class="col-2">
                {{ __('views/admin.floor.edit.direction') }}
            </div>
        </div>

        <?php
        foreach($connectedFloorCandidates as $connectedFloorCandidate){
        /** @var \App\Models\FloorCoupling $floorCoupling */
        if (isset($floorCouplings)) {
            $floorCoupling = $floorCouplings->where('floor1_id', $floor->id)->where('floor2_id', $connectedFloorCandidate->id)->first();
        }
        ?>
        <div class="row mb-3">
            <div class="col-2">
                {!! Form::checkbox(sprintf('floor_%s_connected', $connectedFloorCandidate->id),
                    $connectedFloorCandidate->id, isset($floorCoupling) ? 1 : 0, ['class' => 'form-control left_checkbox']) !!}
            </div>
            <div class="col-8">
                {{ $connectedFloorCandidate->name }}
            </div>
            <div class="col-2">
                {!! Form::select(sprintf('floor_%s_direction', $connectedFloorCandidate->id), [
                            'none' => __('views/admin.floor.edit.floor_direction.none'),
                            'up' => __('views/admin.floor.edit.floor_direction.up'),
                            'down' => __('views/admin.floor.edit.floor_direction.down'),
                            'left' => __('views/admin.floor.edit.floor_direction.left'),
                            'right' => __('views/admin.floor.edit.floor_direction.right')
                        ], isset($floorCoupling) ? $floorCoupling->direction : '', ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <?php } ?>
    @endif

    {!! Form::submit(__('views/admin.floor.edit.submit'), ['class' => 'btn btn-info']) !!}

    {!! Form::close() !!}
@endsection
