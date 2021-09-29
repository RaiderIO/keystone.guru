@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('views/misc.embed.title')])

@section('header-title', __('views/misc.embed.header'))

@section('content')
    <iframe src="{{ route('dungeonroute.embed', ['dungeonroute' => $model]) }}"
            style="width: 800px; height: 600px; border: none;"></iframe>
@endsection
