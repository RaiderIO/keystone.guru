<?php
/** @var $dungeonroute App\Models\DungeonRoute|null */
/** @var $livesession \App\Models\LiveSession|null */
$echo = $echo ?? false;
?>
<nav id="map_header"
     class="map_fade_out navbar navbar-expand-xl {{ $theme === 'lux' ? 'navbar-light' : 'navbar-dark' }}">
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

        <div class="collapse navbar-collapse text-center text-xl-left" id="mainNavbar">
            <ul class="navbar-nav mr-auto">
                @isset($dungeonroute)
                    <li class="nav-item">
                        @isset($livesession)
                            <div class="text-success" disabled>
                                <i class="fas fa-satellite-dish"></i> {{ __('Now live') }}
                            </div>
                        @else
                            <button class="btn btn-success btn-sm" data-toggle="modal"
                                    data-target="#start_live_session_modal">
                                <i class="fas fa-play"></i> {{ __('Start') }}
                            </button>
                        @endisset
                    </li>
                    <li class="nav-item nav-item-divider">

                    </li>
                @endisset
                <li class="nav-item">
                    <div class="row no-gutters">
                        <div id="route_title" class="col h-100 ">
                            <h5 class="mb-0 mr-2">
                                {{ $title }}
                            </h5>
                        </div>
                        @auth
                            @isset($dungeonroute)
                                <div class="col-auto h-100 ml-2">
                                    <i id="route_favorited" class="fas fa-star favorite_star favorited"
                                       style="display: {{ $dungeonroute->isFavoritedByCurrentUser() ? 'inherit' : 'none' }}"></i>
                                    <i id="route_not_favorited" class="far fa-star favorite_star"
                                       style="display: {{ $dungeonroute->isFavoritedByCurrentUser() ? 'none' : 'inherit' }}"></i>
                                    {!! Form::hidden('favorite', $dungeonroute->isFavoritedByCurrentUser() ? '1' : '0', ['id' => 'favorite']) !!}
                                </div>
                            @endisset
                        @endauth
                    </div>
                    @if($dungeonroute && $dungeonroute->team instanceof \App\Models\Team)
                        <div class="row no-gutters">
                            <div class="col text-success">
                                @if($dungeonroute->team->isUserMember(Auth::user()))
                                    <a href="{{ route('team.edit', ['team' => $dungeonroute->team]) }}">
                                        <i class="fas fa-users"></i> {{ $dungeonroute->team->name }}
                                    </a>
                                @else
                                    <i class="fas fa-users"></i> {{ $dungeonroute->team->name }}
                                @endif
                            </div>
                        </div>
                    @endif
                </li>
            </ul>
            @if($echo)
                @include('common.layout.navconnectedusers')
            @endif
            <ul class="navbar-nav">
                <li class="nav-item nav-item-divider">

                </li>
                @auth
                    @if( isset($dungeonroute) && $dungeonroute->isSandbox() )
                        <li class="nav-item mr-2">
                            <a href="{{ route('dungeonroute.claim', ['dungeonroute' => $dungeonroute]) }}">
                                <button class="btn btn-success h-100">
                                    <i class="fas fa-save"></i> {{ __('Save to profile') }}
                                </button>
                            </a>
                        </li>
                    @endif
                @endauth
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

@isset($dungeonroute)
    @component('common.general.modal', ['id' => 'start_live_session_modal'])
        <h3 class="card-title">{{ __('Start live session') }}</h3>

        <p>
            {{ __('Once you start running your route in-game you can create a live session where Keystone.guru will aid you in completing
            your M+ key. Your live session may be shared with anyone by simply copying the URL and sharing it. If your route is assigned
            to a team, any team members currently viewing your route will receive an invitation to your join live session.') }}
        </p>

        <div class="row">
            <div class="col">
                <a href="{{ route('dungeonroute.livesession.create', ['dungeonroute' => $dungeonroute]) }}"
                   class="btn btn-success w-100">
                    <i class="fas fa-play"></i> {{ __('Create live session') }}
                </a>
            </div>
        </div>
    @endcomponent
@endisset