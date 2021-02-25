<?php
/** @var $release \App\Models\Release */
$title = sprintf('%s (%s)', $release->version, $release->created_at->format('Y/m/d'));
?>
@extends('layouts.sitepage', ['showLegalModal' => true, 'title' => $title])

@section('header-title', sprintf('Release %s', $title))
@include('common.general.inline', ['path' => 'release/view', 'options' => ['max_release' => $release->id]])

@section('content')
    @include('common.release.release', ['release' => $release, 'showHeader' => false])
@endsection