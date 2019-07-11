<?php
/** @var $releases \Illuminate\Support\Collection */
/** @var $categories \Illuminate\Support\Collection */
?>
@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Changelog')])

@section('header-title', __('Changelog'))
@include('common.general.inline', ['path' => 'release/view', 'options' => ['max_release' => $releases->first()->id]])

@section('content')
    @foreach($releases as $release)
        @include('common.release.release', ['release' => $release])
    @endforeach

    {{ $releases->links() }}
@endsection