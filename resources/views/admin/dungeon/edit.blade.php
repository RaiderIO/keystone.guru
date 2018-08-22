@extends('layouts.app', ['noads' => true])
@section('header-title')
    {{ $headerTitle }}
@endsection
@section('header-addition')
    <a href="{{ route('admin.dungeons') }}" class="btn btn-info text-white pull-right" role="button">
        <i class="fas fa-backward"></i> {{ __('Dungeon list') }}
    </a>
@endsection
<?php
/**
 * @var $model \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('scripts')
<script type="text/javascript">
    $(function () {
        $('#admin_dungeon_floor_table').DataTable({
        });
    });
</script>
@endsection

@section('content')
<div class="mb-4">
    @isset($model)
        {{ Form::model($model, ['route' => ['admin.dungeon.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'admin.dungeon.savenew']) }}
    @endisset

<div class="form-group{{ $errors->has('active') ? ' has-error' : '' }}">
    {!! Form::label('active', __('Active')) !!}
    {!! Form::checkbox('active', 1, isset($model) ? $model->active : 1, ['class' => 'form-control left_checkbox']) !!}
    @include('common.forms.form-error', ['key' => 'active'])
</div>

<div class="form-group{{ $errors->has('expansion_id') ? ' has-error' : '' }}">
    {!! Form::label('expansion_id', __('Expansion')) !!}
    {!! Form::select('expansion_id', $expansions, null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'expansion_id'])
</div>

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    {!! Form::label('name', __('Dungeon name')) !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'name'])
</div>

{!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

{!! Form::close() !!}
@isset($model)
</div>
<h4>Floor management</h4>
<div class="float-right">
    <a href="{{ route('admin.floor.new', array('dungeon' => $model->id)) }}" class="btn btn-success text-white pull-right" role="button">
        <i class="fas fa-plus"></i> {{ __('Add floor') }}
    </a>
</div>

<table id="admin_dungeon_floor_table" class="tablesorter default_table">
    <thead>
    <tr>
        <th width="10%">{{ __('Id') }}</th>
        <th width="10%">{{ __('Index') }}</th>
        <th width="70%">{{ __('Name') }}</th>
        <th width="10%">{{ __('Actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($model->floors as $floor)
    <tr>
        <td>{{ $floor->id }}</td>
        <td>{{ $floor->index }}</td>
        <td>{{ $floor->name }}</td>
        <td>
            <a class="btn btn-primary" href="{{ route('admin.floor.edit', ['id' => $floor->id]) }}">
                <i class="fas fa-edit"></i>&nbsp;{{ __('Edit') }}
            </a>
        </td>
    </tr>
    @endforeach
    </tbody>

</table>
@endisset
@endsection
