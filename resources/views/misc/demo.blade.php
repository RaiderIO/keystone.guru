@extends('layouts.sitepage', ['title' => __('view_misc.demo.title')])

@section('header-title', __('view_misc.demo.header'))

@section('content')
    @include('common.dungeon.demoroutesgrid', [
        'expansionService' => $expansionService
    ])
@endsection
