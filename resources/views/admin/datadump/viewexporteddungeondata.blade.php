@extends('layouts.app', ['wide' => true])

@section('header-title', __('Dumped dungeon data'))

@section('content')
    <textarea style="width: 100%; height: 100%">{!! $data !!}</textarea>
@endsection