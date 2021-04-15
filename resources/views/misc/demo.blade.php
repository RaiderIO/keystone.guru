@extends('layouts.sitepage', ['title' => __('Demo routes')])

@section('header-title', __('Demo routes'))

@section('content')
    @include('common.dungeon.demoroutesgrid', [
        'expansionService' => $expansionService
    ])
@endsection