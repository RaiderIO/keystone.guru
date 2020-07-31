@extends('layouts.app', ['custom' => true, 'showAds' => false])

@section('header-title', __('Welcome to keystone.guru!'))

@section('content')
    @include('common.general.messages', ['center' => true])

    @if((new Jenssegers\Agent\Agent())->browser() === 'IE')
        <div class="container-fluid alert alert-warning text-center mt-4">
            <div class="container">
            {{ __('It appears you\'re browsing Keystone.guru using Internet Explorer. Unfortunately Internet Explorer is
             not a supported browser. No really, it really do not work at all. Please try either Google Chrome, Mozilla
             Firefox or Microsoft Edge. My apologies.') }}
            </div>
        </div>
    @endif
    <section class="probootstrap-hero mt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-8 offset-md-2 col-sm-8 offset-sm-2 text-center probootstrap-hero-text pb0 probootstrap-animate"
                     data-animate-effect="fadeIn">
                    <h1>{{ __('Welcome to Keystone.guru!') }}</h1>
                    <p>{{ __('Plan your World of Warcraft Mythic Plus routes and share them with your group and the world!') }}</p>
                    <p>
                        <a href="{{ route('dungeonroute.try') }}" class="btn btn-primary btn-ghost btn-lg mt-1"
                           data-toggle="modal" data-target="#try_modal">{{ __('Try it!') }}</a>
                        <a href="{{ route('dungeonroute.view', ['dungeonroute' => \App\Models\DungeonRoute::where('demo', 1)->first()->public_key]) }}"
                           class="btn btn-primary btn-ghost btn-lg mt-1"
                           role="button">{{ __('Demo') }}</a>
                        @guest
                            <a href="#" class="btn btn-primary btn-lg mt-1" role="button" data-toggle="modal"
                               data-target="#register_modal">{{ __('Register and start planning') }}</a>
                        @endguest
                    </p>
                </div>
            </div>

            <div class="row probootstrap-feature-showcase">
                <div class="col-md-4 order-md-8 probootstrap-showcase-nav probootstrap-animate bg-secondary">
                    <ul>
                        <li class="active">
                            <a href="#">{{ __('Interactive maps') }}</a>
                            <p>{{ __('Powered by Leaflet, Keystone.guru features interactive maps with 4 zoom levels and visibility controls. All maps have been upscaled to provide high detail when zoomed in.') }}</p>
                        </li>
                        <li>
                            <a href="#">{{ __('All BFA dungeons supported') }}</a>
                            <p>{{ __('From the depths of The Underrot to the pirate city of Freehold, all current BFA dungeons are supported. In the future, any new dungeons will also be added.') }}</p>
                        </li>
                        <li>
                            <a href="#">{{ __('All enemies added - teeming included') }}</a>
                            <p>{{ __('Ever wondered what different route you could possibly take while still hitting a 100% enemy forces? All enemies are visible on the map, find the alternative route to make your run a success. Includes all enemies that are added on Teeming weeks!') }}</p>
                        </li>
                        <li>
                            <a href="#">{{ __('Awakened affix supported') }}</a>
                            <p>{{ __('Awakened enemies are added, allowing your to plan your skips using N\'Zoth\'s shadow realm.') }}</p>
                        </li>
                        <li>
                            <a href="#">{{ __('Define your setup') }}</a>
                            <p>{{ __('Refine your route and assign races/classes/specializations for each party member and select which affixes the route is for.') }}</p>
                        </li>
                        <li>
                            <a href="#">{{ __('Plan your route') }}</a>
                            <p>{{ __('Plot your route through the dungeon so there can be no confusion on which route you take, or when to split up the group for maximum performance.') }}</p>
                        </li>
                        <li>
                            <a href="#">{{ __('Perfect your plan') }}</a>
                            <p>{{ __('Select which enemies to kill and where, add comments for those tricky parts and refine your route to nail the 100% of enemy forces.') }}</p>
                        </li>
                    </ul>
                </div>
                <div class="col-md-8 order-md-4 probootstrap-animate" style="position: relative;">
                    <div class="probootstrap-home-showcase-wrap">
                        <div class="probootstrap-home-showcase-inner bg-secondary">
                            <div class="probootstrap-image-showcase">
                                <ul class="probootstrap-images-list">
                                    <li class="active">
                                        <img src="images/home/1.jpg" alt="Image" class="img-responsive" loading="lazy">
                                    </li>
                                    <li>
                                        <img src="images/home/2.jpg" alt="Image" class="img-responsive" loading="lazy">
                                    </li>
                                    <li>
                                        <img src="images/home/3.jpg" alt="Image" class="img-responsive" loading="lazy">
                                    </li>
                                    <li>
                                        <img src="images/home/4.jpg" alt="Image" class="img-responsive" loading="lazy">
                                    </li>
                                    <li>
                                        <img src="images/home/5.jpg" alt="Image" class="img-responsive" loading="lazy">
                                    </li>
                                    <li>
                                        <img src="images/home/6.jpg" alt="Image" class="img-responsive" loading="lazy">
                                    </li>
                                    <li>
                                        <img src="images/home/7.jpg" alt="Image" class="img-responsive" loading="lazy">
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="probootstrap-section probootstrap-bg-white probootstrap-zindex-above-showcase bg-secondary">
        <div class="container">
            <div class="row">
                <div class="col-md-6 offset-md-3 text-center section-heading probootstrap-animate"
                     data-animate-effect="fadeIn">
                    <h2>{{ __('Additional Features') }}</h2>
                    <p class="lead">{{ __('Aside from an interactive dungeon map, multiple features of a website allows Keystone.guru to be a hub for all things related to planning your routes with much more to come.') }}</p>
                </div>
            </div>
            <!-- END row -->
            <div class="row probootstrap-gutter60">
                <div class="col-md-4 probootstrap-animate" data-animate-effect="fadeInLeft">
                    <div class="service text-center">
                        <div class="icon"><i class="fa fa-mobile-alt"></i></div>
                        <div class="text">
                            <h3>{{ __('Responsive Design') }}</h3>
                            <p>{{ __('Plan your routes on the go on any device. Keystone.guru is designed with mobility in mind.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 probootstrap-animate" data-animate-effect="fadeIn">
                    <div class="service text-center">
                        <div class="icon"><i class="fa fa-search"></i></div>
                        <div class="text">
                            <h3>{{ __('Route Search') }}</h3>
                            <p>{{ __('Struggle with a dungeon with specific affixes? Search for an existing route made by others, just like your routes can be found by others*.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 probootstrap-animate" data-animate-effect="fadeInRight">
                    <div class="service text-center">
                        <div class="icon"><i class="fa fa-star"></i></div>
                        <div class="text">
                            <h3>{{ __('Rating and Favorites') }}</h3>
                            <p>{{ __('Saw a route that had some great features or you enjoyed running with your group? Rate it for others to discover and favorite it for easy finding for later on through your Profile page!') }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 probootstrap-animate" data-animate-effect="fadeInLeft">
                    <div class="service text-center">
                        <div class="icon"><i class="fa fa-share"></i></div>
                        <div class="text">
                            <h3>{{ __('Easy Sharing') }}</h3>
                            <p>{{ __('Links to routes on Keystone.guru are short and simple. Share your route with your (pug) party members prior to starting and discuss strategy before you wipe.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 probootstrap-animate">
                    <div class="service text-center">
                        <div class="icon"><i class="fab fa-reddit"></i></div>
                        <div class="text">
                            <h3>{{ __('Community') }}</h3>
                            <p>{!! sprintf(__('Join the community on %s, %s or %s for getting in-touch with fellow keystone runners and easy collaboration. I will be around to answer any questions you may have.'),
                                    '<a href="https://reddit.com/r/keystoneguru" target="_blank"><i class="fab fa-reddit"></i> Reddit</a>',
                                    '<a href="https://discord.gg/2KtWrqw" target="_blank"><i class="fab fa-discord"></i> Discord</a>',
                                    '<a href="https://www.youtube.com/channel/UCtjlNmuS2kVQhNvPdW5D2Jg" target="_blank"><i class="fab fa-youtube"></i> Youtube</a>') !!}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 probootstrap-animate" data-animate-effect="fadeInRight">
                    <div class="service text-center">
                        <div class="icon"><i class="fab fa-osi"></i></div>
                        <div class="text">
                            <h3>{{ __('Open Source') }}</h3>
                            <p>{!! sprintf(__('The full source for the entire website can be found on %s. Interested in helping out or have a kick-ass idea for a new feature? Let me know on Github!'),
                            '<a href="https://github.com/Wotuu/keystone.guru" target="_blank"><i class="fab fa-github"></i> Github</a>') !!} </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-center section-heading probootstrap-animate small">
                    <p>{!! sprintf(__('*Prefer some privacy? Consider becoming a %s for unlimited private routes and more.'),
                    '<a href="https://www.patreon.com/keystoneguru" target="_blank"><i class="fab fa-patreon"></i> Patron</a>') !!}</p>
                </div>
            </div>
        </div>
    </section>
@endsection