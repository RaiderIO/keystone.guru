<?php
$user = Auth::user();
?>
@guest
    <li class="nav-item px-3">
        <a class="btn btn-info" href="#" data-toggle="modal" data-target="#login_modal">
            <i class="fas fa-sign-in-alt"></i> {{__('views/common.layout.navuser.login')}}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link px-3" href="#" data-toggle="modal" data-target="#register_modal">
            {{__('views/common.layout.navuser.register')}}
        </a>
    </li>
@else
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            @include('common.user.name', ['user' => $user])
        </a>
        <div class="dropdown-menu text-center text-lg-left" aria-labelledby="navbarDropdown">
            @if( $user->hasRole('admin'))
                @if( config('telescope.enabled') )
                    <a class="dropdown-item"
                       href="{{ route('telescope') }}">
                        <i class="fa fa-binoculars"></i> {{__('views/common.layout.navuser.telescope')}}
                    </a>
                @endif
                <a class="dropdown-item"
                   href="{{ route('admin.tools') }}">
                    <i class="fa fa-hammer"></i> {{__('views/common.layout.navuser.tools')}}
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item"
                   href="{{ route('admin.releases') }}">{{__('views/common.layout.navuser.view_releases')}}</a>
                @if( $user->isAbleTo('read-expansions') )
                    <a class="dropdown-item"
                       href="{{ route('admin.expansions') }}">{{__('views/common.layout.navuser.view_expansions')}}</a>
                @endif
                @if( $user->isAbleTo('read-dungeons') )
                    <a class="dropdown-item"
                       href="{{ route('admin.dungeons') }}">{{__('views/common.layout.navuser.view_dungeons')}}</a>
                @endif
                @if( $user->isAbleTo('read-npcs') )
                    <a class="dropdown-item"
                       href="{{ route('admin.npcs') }}">{{__('views/common.layout.navuser.view_npcs')}}</a>
                @endif
                <a class="dropdown-item"
                   href="{{ route('admin.spells') }}">{{__('views/common.layout.navuser.view_spells')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.users') }}">{{__('views/common.layout.navuser.view_users')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.userreports') }}">{{__('views/common.layout.navuser.view_user_reports') }}
                    @if($numUserReports > 0)
                        <span class="badge badge-primary badge-pill">{{ $numUserReports }}</span>
                    @endif
                </a>
                <div class="dropdown-divider"></div>
            @endif
            <a class="dropdown-item" href="{{ route('profile.view', ['user' => Auth::user()]) }}">
                <i class="fa fa-route"></i> {{ __('views/common.layout.navuser.my_profile') }}
            </a>
            <a class="dropdown-item" href="{{ route('profile.favorites') }}">
                <i class="fa fa-star"></i> {{ __('views/common.layout.navuser.my_favorites') }}
            </a>
            <a class="dropdown-item" href="{{ route('profile.tags') }}">
                <i class="fa fa-tag"></i> {{ __('views/common.layout.navuser.my_tags') }}
            </a>
            <a class="dropdown-item" href="{{ route('team.list') }}">
                <i class="fa fa-users"></i> {{ __('views/common.layout.navuser.my_teams') }}
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                <i class="fa fa-cog"></i> {{ __('views/common.layout.navuser.account_settings') }}
            </a>
            <div class="dropdown-divider"></div>

            <a class="dropdown-item" href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa fa-sign-out-alt"></i> {{ __('views/common.layout.navuser.logout') }}
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                  style="display: none;">
                {{ csrf_field() }}
            </form>
        </div>
    </li>
@endguest
