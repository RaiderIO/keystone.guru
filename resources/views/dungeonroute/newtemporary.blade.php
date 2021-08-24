@extends('layouts.sitepage', ['wide' => false, 'title' => __('views/dungeonroute.newtemporary.title')])
@section('header-title', $headerTitle)

@section('content')
    @include('common.forms.createtemporaryroute')
@endsection

