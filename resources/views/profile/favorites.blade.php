@extends('layouts.sitepage', [
    'rootClass' => 'col-xl-8 offset-xl-2',
    'wide' => true,
    'title' => __('view_profile.favorites.title'),
    'showAds' => false,
])

@section('content')
    @include('common.dungeonroute.table', ['view' => 'favorites'])
@endsection
