@extends('layouts.sitepage', ['wide' => true, 'title' => __('My routes')])

@section('header-title')
    {{ __('My routes') }}
@endsection

@section('content')
    @include('common.general.messages')

    @include('common.dungeonroute.table', ['view' => 'profile'])
@endsection