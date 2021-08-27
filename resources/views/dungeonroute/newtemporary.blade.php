@extends('layouts.sitepage', ['wide' => false, 'title' => __('views/dungeonroute.newtemporary.title')])
@section('header-title', __('views/dungeonroute.newtemporary.header'))

@section('content')
    @include('common.forms.createtemporaryroute')
@endsection

