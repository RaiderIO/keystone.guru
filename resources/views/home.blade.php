<?php
/** @var $demoRoutes \Illuminate\Support\Collection|\App\Models\DungeonRoute[] */
/** @var $demoRouteDungeons \Illuminate\Support\Collection|\App\Models\Dungeon[] */
/** @var $demoRouteMapping array */
/** @var $userCount int */
/** @var $theme string */

$dungeonSelectId    = 'demo_dungeon_id';
$demoRoutesIFrameId = 'demo_routes_iframe';
?>
@extends('layouts.sitepage', ['custom' => true, 'showAds' => false, 'rootClass' => 'home'])

@include('common.general.inline', ['path' => 'home/home', 'options' => [
    'dungeon_select_id' => '#' . $dungeonSelectId,
    'demo_routes_iframe_id' => '#' . $demoRoutesIFrameId,
    'demo_route_mapping' => $demoRouteMapping
]])

@section('content')
    @include('common.general.messages', ['center' => true])

    @if((new Jenssegers\Agent\Agent())->browser() === 'IE')
        @component('common.general.alert', ['type' => 'warning', 'dismiss' => false])
            {{ __('views/home.ie_not_supported') }}
        @endcomponent
    @endif

    <section class="header1 cid-s48MCQYojq mbr-fullscreen mbr-parallax-background" id="header1-f">


        <div class="mbr-overlay"></div>

        <div class="align-center container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-9">
                    <h1 class="mbr-section-title mbr-fonts-style mb-3 display-1">
                        <strong>{{ __('views/home.tagline') }}</strong>
                    </h1>

                    <p class="mbr-text mbr-fonts-style display-7">
                        {{ __('views/home.tagline_description') }}
                    </p>
                    <div class="mbr-section-btn mt-3">
                        <a class="btn btn-primary display-4" href="{{ route('dungeonroutes') }}">
                            <i class="fas fa-binoculars"></i>&nbsp;{{ __('views/home.discover_routes') }}
                        </a>
                        <a class="display-4 btn btn-accent" href="#" data-toggle="modal"
                           data-target="#create_route_modal">
                            <i class="fas fa-plus"></i>&nbsp;{{__('views/home.create_route')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Fade into solid -->
    <section>
        <div class="gradient-top">&nbsp;</div>
    </section>

    <section class="image1 cid-soU8PSECoL" id="image1-n">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="image-wrapper">
                        <img class="darkly_image" src="{{ url('images/home/darkly_feature_discover_new_routes.jpg') }}"
                             alt="{{ __('views/home.discover_new_routes') }}"
                             style="display: {{ $theme === 'darkly' ? 'block' : 'none' }}">
                        <img class="lux_image" src="{{ url('images/home/lux_feature_discover_new_routes.jpg') }}"
                             alt="{{ __('views/home.discover_new_routes') }}"
                             style="display: {{ $theme === 'lux' ? 'block' : 'none' }}">
                        <p class="mbr-description mbr-fonts-style pt-2 align-center display-4">
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>{{ __('views/home.discover_new_routes') }}</strong>
                        </h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            {!! __('views/home.discover_new_routes_description',
                                    ['subcreation' => '<a href="https://mplus.subcreation.net/" target="_blank">mplus.subcreation.net</a>',
                                    'routesearch' => sprintf('<a href="%s" target="_blank">%s</a>', route('dungeonroutes.search'), __('route search'))]) !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="image2 cid-soU8QlAKKP" id="image2-o">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="image-wrapper">
                        <img class="darkly_image"
                             src="{{ url('images/home/darkly_feature_create_your_own_routes.jpg') }}"
                             alt="{{ __('views/home.create_your_own_routes') }}"
                             style="display: {{ $theme === 'darkly' ? 'block' : 'none' }}">
                        <img class="lux_image"
                             src="{{ url('images/home/lux_feature_create_your_own_routes.jpg') }}"
                             alt="{{ __('views/home.create_your_own_routes') }}"
                             style="display: {{ $theme === 'lux' ? 'block' : 'none' }}">
                        <p class="mbr-description mbr-fonts-style mt-2 align-center display-4">
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>{{ __('views/home.create_your_own_routes') }}</strong>
                        </h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            {!! __('views/home.create_your_own_routes_description',
                                    ['createANewRoute' => sprintf('<a href="#" data-toggle="modal" data-target="#create_route_modal">%s</a>', __('create a new route'))]) !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section class="image3 cid-soU8PSECoL" id="image3-n">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="image-wrapper">
                        <img class="darkly_image"
                             src="{{ url('images/home/darkly_feature_get_organized.jpg') }}"
                             alt="{{ __('views/home.get_organized') }}"
                             style="display: {{ $theme === 'darkly' ? 'block' : 'none' }}">
                        <img class="lux_image" src="{{ url('images/home/lux_feature_get_organized.jpg') }}"
                             alt="{{ __('views/home.get_organized') }}"
                             style="display: {{ $theme === 'lux' ? 'block' : 'none' }}">
                        <p class="mbr-description mbr-fonts-style pt-2 align-center display-4">
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>{{ __('views/home.get_organized') }}</strong>
                        </h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            {{ __('views/home.get_organized_description') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="image4 cid-soU8QlAKKP" id="image4-o">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="image-wrapper">
                        <img class="darkly_image"
                             src="{{ url('images/home/darkly_feature_custom_dungeon_mapping.jpg') }}"
                             alt="{{ __('views/home.custom_dungeon_mapping') }}"
                             style="display: {{ $theme === 'darkly' ? 'block' : 'none' }}">
                        <img class="lux_image" src="{{ url('images/home/lux_feature_custom_dungeon_mapping.jpg') }}"
                             alt="{{ __('views/home.custom_dungeon_mapping') }}"
                             style="display: {{ $theme === 'lux' ? 'block' : 'none' }}">
                        <p class="mbr-description mbr-fonts-style mt-2 align-center display-4">
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>{{ __('views/home.custom_dungeon_mapping') }}</strong>
                        </h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            {{ __('views/home.custom_dungeon_mapping_description') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features1 cid-soU6QnGh9A" id="features1-l">


        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-9">
                    <h3 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2">
                        <strong>{{ __('views/home.features') }}</strong>
                    </h3>

                </div>
            </div>
            <div class="row">
                <div class="card col-12 col-md-6 col-lg-3">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <div class="iconfont-wrapper">
                                <i class="fas fa-file-export mbr-iconfont"></i>
                            </div>
                            <h5 class="card-title mbr-fonts-style display-7">
                                <strong>{{ __('views/home.feature_mdt_import_export') }}</strong>
                            </h5>
                            <p class="card-text mbr-fonts-style display-7">
                                {{ __('views/home.feature_mdt_import_export_description') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card col-12 col-md-6 col-lg-3">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <div class="iconfont-wrapper">
                                <i class="fas fa-route mbr-iconfont"></i>
                            </div>
                            <h5 class="card-title mbr-fonts-style display-7">
                                <strong>{{ __('views/home.feature_fluid_mapping_experience') }}</strong>
                            </h5>
                            <p class="card-text mbr-fonts-style display-7">
                                {{ __('views/home.feature_fluid_mapping_experience_description') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card col-12 col-md-6 col-lg-3">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <div class="iconfont-wrapper">
                                <i class="fas fa-mobile-alt mbr-iconfont"></i>
                            </div>
                            <h5 class="card-title mbr-fonts-style display-7">
                                <strong>{{ __('views/home.feature_mobile_friendly') }}</strong>
                            </h5>
                            <p class="card-text mbr-fonts-style display-7">
                                {{ __('views/home.feature_mobile_friendly_description') }}
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="map1 cid-soU5dLgjOI" id="map1-k" style="position: relative;">


        <div class="mbr-section-head mb-4">
            <h3 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2">
                <strong>{{ __('views/home.live_demo') }}</strong>
            </h3>
        </div>

        <div class="align-center container mb-4">
            <div class="row justify-content-center no-gutters">
                <div class="col-xl-4">
                    @include('common.dungeon.select', [
                        'id'       => $dungeonSelectId,
                        'label'    => false,
                        'dungeons' => $demoRouteDungeons,
                        'showAll'  => false,
                        'required' => false
                    ])
                </div>
            </div>
        </div>

        <div class="demo-map" style="position: relative;">
            <div class="demo-loader text-center h-100" style="display: none;">
                <div class="row h-100 justify-content-center align-items-center no-gutters">
                    <div class="col">
                        <h2 style="opacity: 1;">
                            <i class="fas fa-stroopwafel fa-spin"></i> {{ __('views/home.loading') }}
                        </h2>
                    </div>
                </div>
            </div>

            <iframe id="{{ $demoRoutesIFrameId }}"
                    frameborder="0"
                    loading="lazy"
                    class="lazyload"
                    style="border:0; top: 0; left: 0; position: absolute;"
                    data-src="{{ route('dungeonroute.view', ['dungeon' => $demoRoutes->first()->dungeon, 'dungeonroute' => $demoRoutes->first(), 'title' => $demoRoutes->first()->title]) }}"
                    allowfullscreen=""></iframe>
        </div>
    </section>

    <!-- Fade into solid -->
    <section class="gradient-bottom-container">
        <div class="gradient-bottom">&nbsp;</div>
    </section>

    <section class="info3 cid-soU9jtw47v mbr-parallax-background" id="info3-p">

        <div class="mbr-overlay"></div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="card col-12 col-lg-10">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <h4 class="card-title mbr-fonts-style align-center mb-4 display-1">
                                <strong>{{ __('views/home.start_planning_today') }}</strong>
                            </h4>
                            <p class="mbr-text mbr-fonts-style mb-4 display-7">
                                {{ sprintf(__('views/home.join_other_users'), (int)($userCount / 1000) * 1000) }}
                            </p>
                            <div class="mbr-section-btn mt-3">
                                <a class="display-4 btn btn-accent" href="#" data-toggle="modal"
                                   data-target="#create_route_modal">
                                    <i class="fas fa-plus"></i>&nbsp;{{__('views/home.create_route')}}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Fade into solid -->
    <section>
        <div class="gradient-top footer">&nbsp;</div>
    </section>
@endsection
