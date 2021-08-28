@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$dungeon ?? null],
    'showAds' => false,
    'title' => $dungeon ? __('views/admin.dungeon.edit.title_edit') : __('views/admin.dungeon.edit.title_new')
    ])

@section('header-title')
    {{ $dungeon ? __('views/admin.dungeon.edit.header_edit') : __('views/admin.dungeon.edit.header_new') }}
@endsection
<?php
/**
 * @var $dungeon \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_dungeon_floor_table').DataTable({});
        });
    </script>
@endsection

@section('content')
    <div class="mb-4">
        @isset($dungeon)
            {{ Form::model($dungeon, ['route' => ['admin.dungeon.update', $dungeon->slug], 'method' => 'patch']) }}
        @else
            {{ Form::open(['route' => 'admin.dungeon.savenew']) }}
        @endisset

        <div class="form-group{{ $errors->has('active') ? ' has-error' : '' }}">
            {!! Form::label('active', __('views/admin.dungeon.edit.active')) !!}
            {!! Form::checkbox('active', 1, isset($dungeon) ? $dungeon->active : 1, ['class' => 'form-control left_checkbox']) !!}
            @include('common.forms.form-error', ['key' => 'active'])
        </div>

        {{--<div class="form-group{{ $errors->has('expansion_id') ? ' has-error' : '' }}">--}}
        {{--    {!! Form::label('expansion_id', __('Expansion')) !!}--}}
        {{--    {!! Form::select('expansion_id', $expansions, null, ['class' => 'form-control']) !!}--}}
        {{--    @include('common.forms.form-error', ['key' => 'expansion_id'])--}}
        {{--</div>--}}

        <div class="form-group{{ $errors->has('zone_id') ? ' has-error' : '' }}">
            {!! Form::label('zone_id', __('views/admin.dungeon.edit.zone_id')) !!}
            {!! Form::number('zone_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'zone_id'])
        </div>

        <div class="form-group{{ $errors->has('mdt_id') ? ' has-error' : '' }}">
            {!! Form::label('mdt_id', __('views/admin.dungeon.edit.mdt_id')) !!}
            {!! Form::number('mdt_id', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'mdt_id'])
        </div>

        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
            {!! Form::label('name', __('views/admin.dungeon.edit.dungeon_name')) !!}
            {!! Form::text('name', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'name'])
        </div>

        <div class="form-group{{ $errors->has('key') ? ' has-error' : '' }}">
            {!! Form::label('key', __('views/admin.dungeon.edit.key')) !!}
            {!! Form::text('key', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'key'])
        </div>

        <div class="form-group{{ $errors->has('enemy_forces_required') ? ' has-error' : '' }}">
            {!! Form::label('enemy_forces_required', __('views/admin.dungeon.edit.enemy_forces_required')) !!}
            {!! Form::number('enemy_forces_required', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'enemy_forces_required'])
        </div>

        <div class="form-group{{ $errors->has('enemy_forces_required_teeming') ? ' has-error' : '' }}">
            {!! Form::label('enemy_forces_required_teeming', __('views/admin.dungeon.edit.enemy_forces_required_teeming')) !!}
            {!! Form::number('enemy_forces_required_teeming', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'enemy_forces_required_teeming'])
        </div>

        <div class="form-group{{ $errors->has('timer_max_seconds') ? ' has-error' : '' }}">
            {!! Form::label('timer_max_seconds', __('views/admin.dungeon.edit.timer_max_seconds')) !!}
            {!! Form::number('timer_max_seconds', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'timer_max_seconds'])
        </div>

        {!! Form::submit(__('views/admin.dungeon.edit.submit'), ['class' => 'btn btn-info']) !!}

        {!! Form::close() !!}
        @isset($dungeon)
    </div>
    <h4>{{ __('views/admin.dungeon.edit.floor_management') }}</h4>
    <div class="float-right">
        <a href="{{ route('admin.floor.new', ['dungeon' => $dungeon->slug]) }}"
           class="btn btn-success text-white pull-right" role="button">
            <i class="fas fa-plus"></i> {{ __('views/admin.dungeon.edit.add_floor') }}
        </a>
    </div>

    <table id="admin_dungeon_floor_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('views/admin.dungeon.edit.table_header_id') }}</th>
            <th width="10%">{{ __('views/admin.dungeon.edit.table_header_index') }}</th>
            <th width="60%">{{ __('views/admin.dungeon.edit.table_header_name') }}</th>
            <th width="20%">{{ __('views/admin.dungeon.edit.table_header_actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($dungeon->floors as $floor)
            <tr>
                <td>{{ $floor->id }}</td>
                <td>{{ $floor->index }}</td>
                <td>{{ $floor->name }}</td>
                <td>
                    <a class="btn btn-primary"
                       href="{{ route('admin.floor.edit', ['dungeon' => $dungeon->slug, 'floor' => $floor->id]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('views/admin.dungeon.edit.floor_edit_edit') }}
                    </a>
                    <a class="btn btn-primary"
                       href="{{ route('admin.floor.edit.mapping', ['dungeon' => $dungeon->slug, 'floor' => $floor->id]) }}">
                        <i class="fas fa-route"></i>&nbsp;{{ __('views/admin.dungeon.edit.floor_edit_mapping') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
    @endisset
@endsection
