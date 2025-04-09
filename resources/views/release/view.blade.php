<?php
use App\Models\Release;

/**
 * @var Release $release
 */


$title = sprintf('%s (%s)', $release->version, $release->created_at->format('Y/m/d'));
?>
@extends('layouts.sitepage', [
    'showLegalModal' => true,
    'title' => $title,
    'breadcrumbsParams' => [$release]
])

@section('header-title', sprintf(__('view_release.header'), $title))
@include('common.general.inline', ['path' => 'release/view', 'options' => ['max_release' => $release->id]])

@section('content')
    @include('common.release.release', ['release' => $release, 'showHeader' => false])
@endsection
