@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.mdt.dungeonmappinghash.title')])

@section('header-title', __('views/admin.tools.mdt.dungeonmappinghash.header'))

@section('content')
    {{ Form::open(['route' => 'admin.tools.mdt.dungeonmappinghash.submit']) }}
    @include('common.dungeon.select', ['activeOnly' => false, 'showAll' => false])
    <div class="form-group">
        {!! Form::submit(__('views/admin.tools.mdt.dungeonmappinghash.submit'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
    {{ Form::close() }}
@endsection
