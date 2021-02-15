<?php
/** @var $demoRoutes \Illuminate\Support\Collection|\App\Models\DungeonRoute[] */
/** @var $demoRouteDungeons \Illuminate\Support\Collection|\App\Models\Dungeon[] */
/** @var $userCount int */

?>
@extends('layouts.app', ['custom' => true, 'showAds' => false, 'rootClass' => 'home'])

@section('header-title', __('Welcome to keystone.guru!'))

@section('content')
    @include('common.general.messages', ['center' => true])

    @if((new Jenssegers\Agent\Agent())->browser() === 'IE')
        <div class="container-fluid alert alert-warning text-center mt-4">
            <div class="container">
                {{ __('It appears you\'re browsing Keystone.guru using Internet Explorer. Unfortunately Internet Explorer is
                 not a supported browser. No really, it really does not work at all. Please try either Google Chrome, Mozilla
                 Firefox or Microsoft Edge. My apologies.') }}
            </div>
        </div>
    @endif

    <section class="header1 cid-s48MCQYojq mbr-fullscreen mbr-parallax-background" id="header1-f">


        <div class="mbr-overlay"></div>

        <div class="align-center container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-9">
                    <h1 class="mbr-section-title mbr-fonts-style mb-3 display-1"><strong>M+ routes made easy</strong>
                    </h1>

                    <p class="mbr-text mbr-fonts-style display-7">Lorem ipsum dolor sit amet, consectetur adipiscing
                        elit. Mauris vel tincidunt sem. Suspendisse tempus enim non velit aliquet, sed lobortis justo
                        pulvinar. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia
                        curae; Duis suscipit molestie lectus, vel cursus ligula porta id. Duis eu ultricies neque.</p>
                    <div class="mbr-section-btn mt-3">
                        <a class="btn btn-primary display-4" href="https://mobirise.com">Action</a>
                        <a class="btn btn-primary-outline display-4" href="https://mobirise.com">Another action</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="gradient-top">&nbsp;</div>
    </section>

    <!-- Fade into solid -->
    <section>
        <div class="row">
            <div class="col">
                <div class="gradient-top">&nbsp;</div>
            </div>
        </div>
    </section>

    <section class="image1 cid-soU8PSECoL" id="image1-n">


        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="image-wrapper">
                        <img src="{{ url('images/home/1.jpg') }}" alt="Mobirise">
                        <p class="mbr-description mbr-fonts-style pt-2 align-center display-4">
                            Discover new routes
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>Discover new routes</strong></h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris vel tincidunt sem.
                            Suspendisse tempus enim non velit aliquet, sed lobortis justo pulvinar. Vestibulum ante
                            ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Duis suscipit
                            molestie lectus, vel cursus ligula porta id. Duis eu ultricies neque.
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
                        <img src="{{ url('images/home/2.jpg') }}" alt="Mobirise">
                        <p class="mbr-description mbr-fonts-style mt-2 align-center display-4">
                            Create your own
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg">
                    <div class="text-wrapper">
                        <h3 class="mbr-section-title mbr-fonts-style mb-3 display-5">
                            <strong>Create your own</strong>
                        </h3>
                        <p class="mbr-text mbr-fonts-style display-7">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris vel tincidunt sem.
                            Suspendisse tempus enim non velit aliquet, sed lobortis justo pulvinar. Vestibulum ante
                            ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Duis suscipit
                            molestie lectus, vel cursus ligula porta id. Duis eu ultricies neque.
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
                        <strong>Features</strong>
                    </h3>

                </div>
            </div>
            <div class="row">
                <div class="card col-12 col-md-6 col-lg-3">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <div class="iconfont-wrapper">
                                <i class="fas fa-binoculars mbr-iconfont"></i>
                            </div>
                            <h5 class="card-title mbr-fonts-style display-7"><strong>Discover</strong></h5>
                            <p class="card-text mbr-fonts-style display-7">Discover new routes created by other users to
                                lift your M+ game above and beyond.</p>
                        </div>
                    </div>
                </div>
                <div class="card col-12 col-md-6 col-lg-3">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <div class="iconfont-wrapper">
                                <i class="fas fa-route mbr-iconfont"></i>
                            </div>
                            <h5 class="card-title mbr-fonts-style display-7"><strong>Plan</strong></h5>
                            <p class="card-text mbr-fonts-style display-7">Clone an existing route or create your own
                                route.</p>
                        </div>
                    </div>
                </div>
                <div class="card col-12 col-md-6 col-lg-3">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <div class="iconfont-wrapper">
                                <i class="fas fa-users mbr-iconfont"></i>
                            </div>
                            <h5 class="card-title mbr-fonts-style display-7"><strong>Teams</strong></h5>
                            <p class="card-text mbr-fonts-style display-7">Create a team and invite your friends to
                                Keystone.guru. Update your route while changes are synchronized to team members in
                                real-time.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="map1 cid-soU5dLgjOI" id="map1-k">


        <div>
            <div class="mbr-section-head mb-4">
                <h3 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2">
                    <strong>Demo Routes</strong>
                </h3>
            </div>
            <div class="align-center container mb-4">
                <div class="row justify-content-center">
                    <div class="col">
                        @include('common.dungeon.select', [
                            'id'       => 'demo_dungeon_id',
                            'label'    => false,
                            'dungeons' => $demoRouteDungeons,
                            'showAll'  => false,
                            'required' => false
                        ])
                    </div>
                </div>
            </div>
            <div class="demo-map">
                <iframe frameborder="0"
                        loading="lazy"
                        class="lazyload"
                        style="border:0"
                        data-src="{{ route('dungeonroute.view', ['dungeonroute' => $demoRoutes->first()]) }}"
                        allowfullscreen=""></iframe>
            </div>
        </div>
    </section>

    <!-- Fade into solid -->
    <section class="gradient-bottom-container">
        <div class="row">
            <div class="col">
                <div class="gradient-bottom">&nbsp;</div>
            </div>
        </div>
    </section>

    <section class="info3 cid-soU9jtw47v mbr-parallax-background" id="info3-p">

        <div class="mbr-overlay"></div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="card col-12 col-lg-10">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <h4 class="card-title mbr-fonts-style align-center mb-4 display-1">
                                <strong>Start planning today</strong></h4>
                            <p class="mbr-text mbr-fonts-style mb-4 display-7">
                                {{ __(sprintf('Join %d+ other users and plan your M+ routes online!', (int)($userCount / 1000) * 1000)) }}
                            </p>
                            <div class="mbr-section-btn mt-3">
                                <div class="btn btn-primary display-4">{{ __('Create route') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Fade into solid -->
    <section>
        <div class="row">
            <div class="col">
                <div class="gradient-top">&nbsp;</div>
            </div>
        </div>
    </section>
@endsection