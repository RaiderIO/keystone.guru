<?php
$echo = isset($echo) ? $echo : false;
?>
<nav id="map_header" class="navbar navbar-expand-lg {{ $theme === 'lux' ? 'navbar-light' : 'navbar-dark' }}">
    <div class="container bg-header">
        <a class="navbar-brand" href="/">
            <img src="{{ url('/images/logo/logo_and_text.png') }}" alt="{{ config('app.name', 'Laravel') }}"
                 height="44px;" width="200px;">
        </a>

        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse text-center text-lg-left" id="mainNavbar">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <h5 class="mb-0 mr-2">
                        {{ $title }}
                    </h5>
                </li>
                @auth
                    @isset($dungeonroute)
                        <li class="nav-item">
                            <h5 class="mb-0">
                                <i id="route_favorited" class="fas fa-star favorite_star favorited"
                                   style="display: {{ $model->isFavoritedByCurrentUser() ? 'inherit' : 'none' }}"></i>
                                <i id="route_not_favorited" class="far fa-star favorite_star"
                                   style="display: {{ $model->isFavoritedByCurrentUser() ? 'none' : 'inherit' }}"></i>
                                {!! Form::hidden('favorite', $model->isFavoritedByCurrentUser() ? '1' : '0', ['id' => 'favorite']) !!}
                            </h5>
                        </li>
                    @endisset
                @endauth
            </ul>
            @if($echo)
                @include('common.layout.navconnectedusers')
            @endif
            <ul class="navbar-nav">
                <li class="nav-item nav-item-divider">

                </li>
                <li class="nav-item">
                    <button class="btn btn-info h-100" data-toggle="modal" data-target="#share_modal">
                        <i class="fas fa-share"></i> {{ __('Share') }}
                    </button>
                </li>
                <li class="nav-item nav-item-divider">

                </li>
                @include('common.layout.navuser')
                @include('common.layout.navthemeswitch')
            </ul>
        </div>
    </div>
</nav>