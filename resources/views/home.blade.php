<?php
/** @var $demoRoutes \Illuminate\Support\Collection|\App\Models\DungeonRoute[] */
/** @var $demoRouteDungeons \Illuminate\Support\Collection|\App\Models\Dungeon[] */
/** @var $demoRouteMapping array */
/** @var $userCount int */
/** @var $theme string */

$dungeonSelectId = 'demo_dungeon_id';
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
            {{ __('It appears you\'re browsing Keystone.guru using Internet Explorer. Unfortunately Internet Explorer is
             not a supported browser. No really, it really does not work at all. Please try either Google Chrome, Mozilla
             Firefox or Microsoft Edge.') }}
        @endcomponent
    @endif

    <section class="header1 cid-s48MCQYojq mbr-fullscreen mbr-parallax-background" id="header1-f">


        <div class="mbr-overlay"></div>

        <div class="align-center container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-9">
                    <h1 class="mbr-section-title mbr-fonts-style mb-3 display-1">
                        <strong>{{ __('M+ routes made easy') }}</strong>
                    </h1>

                    <p class="mbr-text mbr-fonts-style display-7">
                        {{ __('Plan routes online cooperatively with your team or discover routes that suit your play style and skill level. Keystone.guru is the one
                                place to manage and share your M+ routes.
') }}
                    </p>
                    <div class="mbr-section-btn mt-3">
                        <a class="btn btn-primary display-4" href="{{ route('dungeonroutes') }}">
                            <i class="fas fa-binoculars"></i>&nbsp;{{ __('Discover routes') }}
                        </a>
                        <a class="display-4 btn btn-accent" href="#" data-toggle="modal"
                           data-target="#create_route_modal">
                            <i class="fas fa-plus"></i>&nbsp;{{__('Create route')}}
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
                             alt="{{ __('Discover new routes') }}"
                             style="display: {{ $theme === 'darkly' ? 'block' : 'none' }}">
                        <img class="lux_image" src="{{ url('images/home/lux_feature_discover_new_routes.jpg') }}"
                             alt="{{ __('Discover new routes') }}"
                             style="display: {{ $theme === 'lux' ? 'block' : 'none' }}">
                        <p class="mbr-description mbr-fonts-style pt-2 align-center display-4">
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>{{ __('Discover new routes') }}</strong>
                        </h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            {!! __('Easily browse for routes per dungeon in various categories to find a route that suits your group perfectly.
                                    Integration with :subcreation makes it easy to see which dungeons are the easiest to time for any affix.
                                    Still can\'t find a route that suits your needs? The :routesearch page allows you to dial in on your exact needs to find a perfect match.',
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
                             alt="{{ __('Create your own routes') }}"
                             style="display: {{ $theme === 'darkly' ? 'block' : 'none' }}">
                        <img class="lux_image" src="{{ url('images/home/lux_feature_create_your_own_routes.jpg') }}"
                             alt="{{ __('Create your own routes') }}"
                             style="display: {{ $theme === 'lux' ? 'block' : 'none' }}">
                        <p class="mbr-description mbr-fonts-style mt-2 align-center display-4">
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>{{ __('Create your own routes') }}</strong>
                        </h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            {!! __('Import your routes from Mythic Dungeon Tools or :createANewRoute from scratch. Keystone.guru offers various tools to make your route a memorable one,
                                    such as free drawing, pathing and placing of icons/comments. Enemy forces can be displayed raw or in percentage on a whim. Various other settings allow you
                                    to customize your route creation experience to your liking.',
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
                        <img src="{{ url(sprintf('images/home/%s_feature_get_organized.jpg', $theme)) }}"
                             alt="{{ __('Get organized') }}">
                        <p class="mbr-description mbr-fonts-style pt-2 align-center display-4">
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>{{ __('Get organized') }}</strong>
                        </h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            {{ __('Organize your routes from your profile or through teams. Keystone.guru offers you a wide array of tools to keep all your routes organized
                                    and accessible by all your M+ team members. You can always export routes to Mythic Dungeon Tools format to share them with others.') }}
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
                             alt="{{ __('Custom dungeon mapping') }}"
                             style="display: {{ $theme === 'darkly' ? 'block' : 'none' }}">
                        <img class="lux_image" src="{{ url('images/home/lux_feature_custom_dungeon_mapping.jpg') }}"
                             alt="{{ __('Custom dungeon mapping') }}"
                             style="display: {{ $theme === 'lux' ? 'block' : 'none' }}">
                        <p class="mbr-description mbr-fonts-style mt-2 align-center display-4">
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>{{ __('Custom dungeon mapping') }}</strong>
                        </h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            {{ __('Keystone.guru has its own dungeon mapping with no dependencies on any external tool. View which enemies bolster others, drop sanguine ichor or burst your party. The mapping is open source and free. Always.') }}
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
                        <strong>{{ __('Features') }}</strong>
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
                                <strong>{{ __('MDT import/export') }}</strong>
                            </h5>
                            <p class="card-text mbr-fonts-style display-7">
                                {{ __('Get started with your existing routes easily and generate MDT strings for your existing routes so everyone\'s up-to-date.') }}
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
                                <strong>{{ __('Fluid mapping experience') }}</strong>
                            </h5>
                            <p class="card-text mbr-fonts-style display-7">
                                {{ __('Powered by Leaflet, AI enhanced dungeon maps with 5 zoom levels with a minimalistic UI to give you the best mapping experience possible.') }}
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
                                <strong>{{ __('Mobile friendly') }}</strong>
                            </h5>
                            <p class="card-text mbr-fonts-style display-7">
                                {{ __('View or edit your routes anywhere you are on your phone or tablet - making toilet breaks that much more interesting.') }}
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
                <strong>{{ __('Live demo') }}</strong>
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
                            <i class="fas fa-stroopwafel fa-spin"></i> {{ __('Loading...') }}
                        </h2>
                    </div>
                </div>
            </div>

            <iframe id="{{ $demoRoutesIFrameId }}"
                    frameborder="0"
                    loading="lazy"
                    class="lazyload"
                    style="border:0; top: 0; left: 0; position: absolute;"
                    data-src="{{ route('dungeonroute.view', ['dungeonroute' => $demoRoutes->first()]) }}"
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
                                <strong>{{ __('Start planning today') }}</strong>
                            </h4>
                            <p class="mbr-text mbr-fonts-style mb-4 display-7">
                                {{ sprintf(__('Join %d+ other users and plan your M+ routes online!'), (int)($userCount / 1000) * 1000) }}
                            </p>
                            <div class="mbr-section-btn mt-3">
                                <a class="display-4 btn btn-accent" href="#" data-toggle="modal"
                                   data-target="#create_route_modal">
                                    <i class="fas fa-plus"></i>&nbsp;{{__('Create route')}}
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