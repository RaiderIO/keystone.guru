@extends('layouts.sitepage', ['rootClass' => 'col-xl-8 offset-xl-2', 'wide' => true, 'title' => __('views/profile.favorites.title')])

@section('content')
    @include('common.general.messages')

    @include('common.dungeonroute.table', ['view' => 'favorites'])
@endsection
