@extends('layouts.sitepage', ['title' => __('views/misc.demo.title')])

@section('header-title', __('views/misc.demo.header'))

@section('content')
    @include('common.dungeon.demoroutesgrid', [
        'expansionService' => $expansionService
    ])
@endsection