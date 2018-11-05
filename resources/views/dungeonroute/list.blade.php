@extends('layouts.app', ['wide' => true])

@section('header-title')
    {{ __('Route listing') }}
@endsection
<?php
/**
 * @var $models \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('content')
    <div class="text-info text-center mb-2">
        <i class="fas fa-info-circle"></i>
        {!! sprintf(
        __('Did you know Keystone.guru supports the Infested affix? For more information please read the %s!'),
        '<a href="https://www.reddit.com/r/KeystoneGuru/comments/9t8ctx/the_site_has_infested_support_heres_how_to_use_it/">tutorial <i class="fas fa-external-link-alt"></i></a>'
        ) !!}
    </div>

    @include('common.dungeonroute.table')
@endsection()