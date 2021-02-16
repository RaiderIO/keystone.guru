@extends('layouts.app', ['wide' => false, 'title' => __('Create route')])
@section('header-title', $headerTitle)

@section('content')
    @include('common.forms.createroute', ['model' => $model ?? null])
@endsection

