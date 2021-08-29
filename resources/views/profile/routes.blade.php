@extends('layouts.sitepage', ['rootClass' => 'col-xl-10 offset-xl-1', 'wide' => true, 'title' => __('views/profile.routes.title')])

@section('content')
    @include('common.general.messages')

    @include('common.dungeonroute.table', ['view' => 'profile'])
@endsection