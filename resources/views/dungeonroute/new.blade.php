@extends('layouts.app', ['wide' => true])
@section('header-title', $headerTitle)

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['dungeonroute.update', $model->id], 'method' => 'patch']) }}
    @else
        {{ Form::open(['route' => 'dungeonroute.savenew']) }}
    @endisset
    <div class="container {{ isset($model) ? 'hidden' : '' }}">
        <h3>
            {{ __('General') }}
        </h3>
        <div class="form-group">
            {!! Form::label('dungeon_route_title', __('Title') . "*") !!}
            {!! Form::text('dungeon_route_title', '', ['class' => 'form-control']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('dungeon_id', __('Select dungeon') . "*") !!}
            {!! Form::select('dungeon_id', \App\Models\Dungeon::active()->pluck('name', 'id'), null, ['class' => 'form-control']) !!}
        </div>

        <h3>
            {{ __('Group composition (optional)') }}
        </h3>
        @include('common.group.composition')

        <h3>
            {{ __('Affixes (optional)') }}
        </h3>

        @include('common.group.affixes')

        <h3>
            {{ __('Sharing') }}
        </h3>
        <div class="form-group">
            {!! Form::label('unlisted', __('Unlisted (only people with the link may view your route)')) !!}
            {!! Form::checkbox('unlisted', 1, 0, ['class' => 'form-control left_checkbox']) !!}
        </div>

        <div class="col-lg-12">
            <div class="form-group">
                {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}
            </div>
        </div>
    </div>

    {!! Form::close() !!}
@endsection

