<?php
/** @var $theme string */
/** @var $dungeonroute \App\Models\DungeonRoute|null */
/** @var $livesession \App\Models\LiveSession|null */
$echo = $echo ?? false;
?>
<nav id="map_header"
     class="map_fade_out navbar navbar-expand-xl {{ $theme === 'lux' ? 'navbar-light' : 'navbar-dark' }}">
    <div class="container bg-header">
        <a class="navbar-brand" href="/">
            <img src="{{ url('/images/logo/logo_and_text.png') }}" alt="{{ config('app.name', 'Keystone.guru') }}"
                 height="44px;" width="200px;">
        </a>

        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="{{ __('views/common.maps.controls.header.toggle_navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse text-center text-xl-left" id="mainNavbar">
            <ul class="navbar-nav mr-auto">
                @isset($dungeonroute)
                    <li class="nav-item">
                        <div class="d-flex h-100">
                            <div class="row justify-content-center align-self-center">
                                <div class="col">
                                    @isset($livesession)
                                        <?php $stopped = $livesession->expires_at !== null; ?>
                                        @if(!$stopped)
                                            <button id="stop_live_session" class="btn btn-danger btn-sm"
                                                    data-toggle="modal" data-target="#stop_live_session_modal">
                                                <i class="fas fa-stop"></i> {{ __('views/common.maps.controls.header.stop') }}
                                            </button>
                                        @endif
                                        <div id="stopped_live_session_container" class="row no-gutters"
                                             style="display: {{ $stopped ? 'inherit' : 'none' }}">
                                            <div class="row">
                                                <div class="col">
                                                <span id="stopped_live_session_countdown">
                                                    {{ $stopped ? sprintf(__('views/common.maps.controls.header.live_session_expires_in'), $livesession->getExpiresInHoursSeconds()) : '' }}
                                                </span>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col">
                                                    @if($dungeonroute->mayUserEdit(Auth::user()))
                                                        <a href="{{ route('dungeonroute.edit', ['dungeonroute' => $dungeonroute]) }}"
                                                           class="btn-sm btn-success w-100">
                                                            <i class="fas fa-edit"></i> {{ __('views/common.maps.controls.header.edit_route') }}
                                                        </a>
                                                    @else
                                                        <a href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute]) }}"
                                                           class="btn-sm btn-success w-100">
                                                            <i class="fas fa-eye"></i> {{ __('views/common.maps.controls.header.view_route') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <button class="btn btn-success btn-sm" data-toggle="modal"
                                                data-target="#start_live_session_modal">
                                            <i class="fas fa-play"></i> {{ __('views/common.maps.controls.header.start') }}
                                        </button>
                                    @endisset
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item nav-item-divider">

                    </li>
                @endisset
                <li class="nav-item h-100">
                    <div class="row no-gutters justify-content-center align-self-center">
                        <div id="route_title" class="col">
                            <h5 class="mb-0 mr-2">
                                {{ $title }}
                            </h5>
                        </div>
                        @auth
                            @isset($dungeonroute)
                                <div class="col-auto ml-2">
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
                            <div class="col text-primary">
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
                                    <i class="fas fa-save"></i> {{ __('views/common.maps.controls.header.save_to_profile') }}
                                </button>
                            </a>
                        </li>
                    @endif
                @endauth
                <li class="nav-item">
                    <button class="btn btn-info h-100" data-toggle="modal" data-target="#share_modal">
                        <i class="fas fa-share"></i> {{ __('views/common.maps.controls.header.share') }}
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
        <h3 class="card-title">{{ __('views/common.maps.controls.header.start_live_session') }}</h3>

        <p>
            {{ __('views/common.maps.controls.header.start_live_session_paragraph_1') }}
            <br><br>
            {{ __('views/common.maps.controls.header.start_live_session_paragraph_2') }}
            <br><br>
            {{ __('views/common.maps.controls.header.start_live_session_paragraph_3') }}
            <br><br>
            {{ __('views/common.maps.controls.header.start_live_session_paragraph_4') }}
        </p>

        <div class="row">
            <div class="col">
                <a href="{{ route('dungeonroute.livesession.create', ['dungeonroute' => $dungeonroute]) }}"
                   class="btn btn-success w-100">
                    <i class="fas fa-play"></i> {{ __('views/common.maps.controls.header.create_live_session') }}
                </a>
            </div>
        </div>
    @endcomponent

    @component('common.general.modal', ['id' => 'stop_live_session_modal'])
        <h3 class="card-title">{{ __('views/common.maps.controls.header.live_session_concluded') }}</h3>

        <?php // You cannot rate your own routes ?>
        @if($dungeonroute->author_id !== Auth::id())
            <?php $currentRating = $dungeonroute->getRatingByCurrentUser() ?>
            <div class="form-group">
                <h5>
                    {{ __('views/common.maps.controls.header.rate_this_route') }}
                </h5>
                <select>
                    @for($i = 1; $i <= 10; $i++)
                        <option
                            value="{{ $i }}" {{ $currentRating !== false && (int) $currentRating === $i ? 'selected' : '' }}>
                            {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>

            @if($currentRating === false)
                <div class="form-group">
                    <p>
                        {{ __('views/common.maps.controls.header.rate_this_route_explanation') }}
                    </p>
                </div>
            @endif
        @else
            <div class="form-group">
                <p>
                    {{ __('views/common.maps.controls.header.you_cannot_rate_your_own_route') }}
                </p>
            </div>
        @endif

        <div class="row form-group">
            <div class="col">
                <button data-dismiss="modal" class="btn btn-outline-info w-100">
                    <i class="fas fa-chart-line"></i> {{ __('views/common.maps.controls.header.review_live_session') }}
                </button>
            </div>
            <div class="col">
                @if($dungeonroute->mayUserEdit(Auth::user()))
                    <a href="{{ route('dungeonroute.edit', ['dungeonroute' => $dungeonroute]) }}"
                       class="btn btn-success w-100">
                        <i class="fas fa-edit"></i> {{ __('views/common.maps.controls.header.edit_route') }}
                    </a>
                @else
                    <a href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute]) }}"
                       class="btn btn-success w-100">
                        <i class="fas fa-eye"></i> {{ __('views/common.maps.controls.header.view_route') }}
                    </a>
                @endif
            </div>
        </div>
    @endcomponent
@endisset
