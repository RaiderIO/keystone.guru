@extends('layouts.app', ['custom' => true])

@section('header-title', __('Welcome to keystone.guru!'))

@section('content')
    <section class="probootstrap-hero mt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-8 offset-md-2 col-sm-8 offset-sm-2 text-center probootstrap-hero-text pb0 probootstrap-animate"
                     data-animate-effect="fadeIn">
                    <h1>{{ __('Welcome to Keystone.guru!') }}</h1>
                    <p>{{ __('This website allows you to plan routes through your Mythic Plus dungeons and share them with the world or your group!') }}</p>
                    <p>
                        <a href="#" class="btn btn-primary btn-lg" role="button">{{ __('Register and start planning') }}</a>
                        <a href="#" class="btn btn-primary btn-ghost btn-lg mt-md-0 mt-sm-3" role="button">{{ __('Demo') }}</a>
                    </p>
                    <!-- <p><a href="#"><i class="icon-play2"></i> Watch the video</a></p> -->
                </div>
            </div>

            <div class="row probootstrap-feature-showcase">
                <div class="col-md-4 order-md-8 probootstrap-showcase-nav probootstrap-animate bg-secondary">
                    <ul>
                        <li class="active">
                            <a href="#">{{ __('Interactive maps') }}</a>
                            <p>{{ __('Powered by Leaflet, Keystone.guru features interactive maps with 3 zoom levels and visibility controls.') }}</p>
                        </li>
                        <li>
                            <a href="#">{{ __('All BFA dungeons supported') }}</a>
                            <p>{{ __('From the depths of The Underrot to the pirate city of Freehold, all current BFA dungeons are supported. In the future, any new dungeons will also be added.') }}</p>
                        </li>
                        <li>
                            <a href="#">{{ __('All enemies added - teeming included') }}</a>
                            <p>{{ __('Ever wondered what different route you could possibly take while still hitting a 100% enemy forces? All enemies are visible on the map, find the alternative route to make your run a success. Includes all enemies that are added on Teeming weeks, too!') }}</p>
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
                                    <li class="active"><img src="images/landing/img_showcase_2.jpg" alt="Image"
                                                            class="img-responsive"></li>
                                    <li><img src="images/landing/img_showcase_1.jpg" alt="Image" class="img-responsive">
                                    </li>
                                    <li><img src="images/landing/img_showcase_2.jpg" alt="Image" class="img-responsive">
                                    </li>
                                    <li><img src="images/landing/img_showcase_1.jpg" alt="Image" class="img-responsive">
                                    </li>
                                    <li><img src="images/landing/img_showcase_2.jpg" alt="Image" class="img-responsive">
                                    </li>
                                    <li><img src="images/landing/img_showcase_1.jpg" alt="Image" class="img-responsive">
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
                            <h3>{{ __('Rating and Favoriting') }}</h3>
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
                            <p>{!! sprintf(__('Join the community on %s or %s for getting in-touch with fellow keystone runners and easy sharing of routes. I will be around to answer any questions you may have.'),
                                    '<a href="https://reddit.com/r/keystoneguru"><i class="fab fa-reddit"></i> Reddit</a>', '<a href="https://discord.gg/2KtWrqw"><i class="fab fa-discord"></i> Discord</a>') !!}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 probootstrap-animate" data-animate-effect="fadeInRight">
                    <div class="service text-center">
                        <div class="icon"><i class="fab fa-osi"></i></div>
                        <div class="text">
                            <h3>{{ __('Open Source') }}</h3>
                            <p>{!! sprintf(__('The full source for the entire website can be found on %s. Interested in helping out or have a kick-ass idea for a new feature? Let me know on Github!'),
                            '<a href="https://github.com/Wotuu/keystone.guru"><i class="fab fa-github"></i> Github</a>') !!} </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-center section-heading probootstrap-animate small">
                    <p>{!! sprintf(__('*Prefer some privacy? Consider becoming a %s for unlimited private routes and more.'), '<a href="https://www.patreon.com/keystoneguru"><i class="fab fa-patreon"></i> Patron</a>') !!}</p>
                </div>
            </div>
        </div>
    </section>
<!--

    <section class="probootstrap-section probootstrap-bg-white probootstrap-zindex-above-showcase">
        <div class="container">
            <div class="row">
                <div class="col-md-6 offset-md-3 text-center section-heading probootstrap-animate">
                    <h2>More Features</h2>
                    <p class="lead">Lorem ipsum dolor sit amet consectetur adipisicing elit. Iusto provident qui tempore
                        natus quos quibusdam soluta at.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-7 order-md-5 probootstrap-animate" data-animate-effect="fadeInRight">

                    <div class="owl-carousel owl-carousel-fullwidth border-rounded">
                        <div class="item">
                            <img src="images/landing/img_showcase_1.jpg"
                                 alt="Free HTML5 Bootstrap Template by GetTemplates.co">
                        </div>
                        <div class="item">
                            <img src="images/landing/img_showcase_2.jpg"
                                 alt="Free HTML5 Bootstrap Template by GetTemplates.co">
                        </div>
                        <div class="item">
                            <img src="images/landing/img_showcase_1.jpg"
                                 alt="Free HTML5 Bootstrap Template by GetTemplates.co">
                        </div>
                        <div class="item">
                            <img src="images/landing/img_showcase_2.jpg"
                                 alt="Free HTML5 Bootstrap Template by GetTemplates.co">
                        </div>
                    </div>

                </div>

                <div class="col-md-5 order-md-7">
                    <div class="service left-icon probootstrap-animate" data-animate-effect="fadeInLeft">
                        <div class="icon"><i class="icon-mobile3"></i></div>
                        <div class="text">
                            <h3>Responsive Design</h3>
                            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit iusto provident.</p>
                        </div>
                    </div>
                    <div class="service left-icon probootstrap-animate" data-animate-effect="fadeInLeft">
                        <div class="icon"><i class="icon-presentation"></i></div>
                        <div class="text">
                            <h3>Business Solutions</h3>
                            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit iusto provident.</p>
                        </div>
                    </div>
                    <div class="service left-icon probootstrap-animate" data-animate-effect="fadeInLeft">
                        <div class="icon"><i class="icon-circle-compass"></i></div>
                        <div class="text">
                            <h3>Brand Identity</h3>
                            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit iusto provident.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    -->


    <!-- Modal login -->
    <div class="modal fadeInUp probootstrap-animated" id="loginModal" tabindex="-1" role="dialog"
         aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="vertical-alignment-helper">
            <div class="modal-dialog modal-md vertical-align-center">
                <div class="modal-content">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i
                                class="icon-cross"></i></button>
                    <div class="probootstrap-modal-flex">
                        <div class="probootstrap-modal-figure"
                             style="background-image: url(images/landing/modal_bg.jpg);"></div>
                        <div class="probootstrap-modal-content">
                            <form action="#" class="probootstrap-form">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="Email">
                                </div>
                                <div class="form-group">
                                    <input type="password" class="form-control" placeholder="Password">
                                </div>
                                <div class="form-group clearfix mb40">
                                    <label for="remember" class="probootstrap-remember"><input type="checkbox"
                                                                                               id="remember"> Remember
                                        Me</label>
                                    <a href="#" class="probootstrap-forgot">Forgot Password?</a>
                                </div>
                                <div class="form-group text-left">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="submit" class="btn btn-primary btn-block" value="Sign In">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group probootstrap-or">
                                    <span><em>or</em></span>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button class="btn btn-primary btn-ghost btn-block btn-connect-facebook">
                                                <span>connect with</span> Facebook
                                            </button>
                                            <button class="btn btn-primary btn-ghost btn-block btn-connect-google">
                                                <span>connect with</span> Google
                                            </button>
                                            <button class="btn btn-primary btn-ghost btn-block btn-connect-twitter">
                                                <span>connect with</span> Twitter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END modal login -->

    <!-- Modal signup -->
    <div class="modal fadeInUp probootstrap-animated" id="signupModal" tabindex="-1" role="dialog"
         aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="vertical-alignment-helper">
            <div class="modal-dialog modal-md vertical-align-center">
                <div class="modal-content">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i
                                class="icon-cross"></i></button>
                    <div class="probootstrap-modal-flex">
                        <div class="probootstrap-modal-figure"
                             style="background-image: url(images/landing/modal_bg.jpg);"></div>
                        <div class="probootstrap-modal-content">
                            <form action="#" class="probootstrap-form">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="Email">
                                </div>
                                <div class="form-group">
                                    <input type="password" class="form-control" placeholder="Password">
                                </div>
                                <div class="form-group">
                                    <input type="password" class="form-control" placeholder="Re-type Password">
                                </div>
                                <div class="form-group clearfix mb40">
                                    <label for="remember" class="probootstrap-remember"><input type="checkbox"
                                                                                               id="remember"> Remember
                                        Me</label>
                                    <a href="#" class="probootstrap-forgot">Forgot Password?</a>
                                </div>
                                <div class="form-group text-left">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="submit" class="btn btn-primary btn-block" value="Sign Up">
                                        </div>
                                    </div>

                                </div>
                                <div class="form-group probootstrap-or">
                                    <span><em>or</em></span>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button class="btn btn-primary btn-ghost btn-block btn-connect-facebook">
                                                <span>connect with</span> Facebook
                                            </button>
                                            <button class="btn btn-primary btn-ghost btn-block btn-connect-google">
                                                <span>connect with</span> Google
                                            </button>
                                            <button class="btn btn-primary btn-ghost btn-block btn-connect-twitter">
                                                <span>connect with</span> Twitter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END modal signup -->

@endsection