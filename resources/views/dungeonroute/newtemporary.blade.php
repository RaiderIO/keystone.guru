@extends('layouts.sitepage', ['wide' => false, 'title' => __('view_dungeonroute.newtemporary.title')])
@section('header-title', __('view_dungeonroute.newtemporary.header'))

@section('content')
    @include('common.forms.createtemporaryroute')
@endsection

