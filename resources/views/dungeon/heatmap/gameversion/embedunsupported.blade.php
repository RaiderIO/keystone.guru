<?php

use App\Models\Dungeon;

/**
 * @var Dungeon $dungeon
 * @var string  $title
 */

?>
@extends('layouts.map', [
    'showAds' => false,
    'custom' => true,
    'footer' => false,
    'header' => false,
    'title' => $title,
    'bodyClass' => 'overflow-hidden',
    'cookieConsent' => false,
])

@section('content')
    <header class="header_embed_compact px-0 px-md-2"
            style="background-image: url({{ $dungeon->getImageUrl() }}); background-size: cover;">
        <div class="row g-0 py-2">
            @include('common.embed.header.compact.logo')
        </div>
    </header>

    <div class="wrapper embed_wrapper compact d-flex align-items-center justify-content-center text-center p-4">
        <p class="mb-0">{{ __('view_dungeon.heatmap.gameversion.embed.not_supported', ['dungeon' => $title]) }}</p>
    </div>
@endsection
