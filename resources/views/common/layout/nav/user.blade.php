<?php

use App\Models\Laratrust\Role;

$user = Auth::user();
?>
@guest
    <li class="nav-item px-3">
        <a class="btn btn-info" href="#" data-toggle="modal" data-target="#login_modal">
            <i class="fas fa-sign-in-alt"></i> {{__('view_common.layout.nav.user.login')}}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link px-3" href="#" data-toggle="modal" data-target="#register_modal">
            {{__('view_common.layout.nav.user.register')}}
        </a>
    </li>
@else
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            @include('common.user.name', ['user' => $user])
        </a>
        <div class="dropdown-menu text-center text-xl-left" aria-labelledby="navbarDropdown">
            @if($user->hasRole(Role::ROLE_ADMIN))
                @if( config('telescope.enabled') )
                    <a class="dropdown-item"
                       href="{{ route('telescope') }}">
                        <i class="fa fa-binoculars"></i> {{__('view_common.layout.nav.user.telescope')}}
                    </a>
                @endif
                <a class="dropdown-item"
                   href="{{ route('admin.tools') }}">
                    <i class="fa fa-hammer"></i> {{__('view_common.layout.nav.user.tools')}}
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item"
                   href="{{ route('admin.releases') }}">{{__('view_common.layout.nav.user.view_releases')}}</a>
                @if( $user->isAbleTo('read-expansions') )
                    <a class="dropdown-item"
                       href="{{ route('admin.expansions') }}">{{__('view_common.layout.nav.user.view_expansions')}}</a>
                @endif
                @if( $user->isAbleTo('read-dungeons') )
                    <a class="dropdown-item"
                       href="{{ route('admin.dungeons') }}">{{__('view_common.layout.nav.user.view_dungeons')}}</a>
                @endif
                @if( $user->isAbleTo('read-npcs') )
                    <a class="dropdown-item"
                       href="{{ route('admin.npcs') }}">{{__('view_common.layout.nav.user.view_npcs')}}</a>
                @endif
                <a class="dropdown-item"
                   href="{{ route('admin.spells') }}">{{__('view_common.layout.nav.user.view_spells')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.users') }}">{{__('view_common.layout.nav.user.view_users')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.userreports') }}">{{__('view_common.layout.nav.user.view_user_reports') }}
                    @if($numUserReports > 0)
                        <span class="badge badge-primary badge-pill">{{ $numUserReports }}</span>
                    @endif
                </a>
                <div class="dropdown-divider"></div>
            @endif
            <a class="dropdown-item" href="{{ route('profile.view', ['user' => Auth::user()]) }}">
                <i class="fa fa-route"></i> {{ __('view_common.layout.nav.user.my_profile') }}
            </a>
            <a class="dropdown-item" href="{{ route('profile.favorites') }}">
                <i class="fa fa-star"></i> {{ __('view_common.layout.nav.user.my_favorites') }}
            </a>
            <a class="dropdown-item" href="{{ route('profile.tags') }}">
                <i class="fa fa-tag"></i> {{ __('view_common.layout.nav.user.my_tags') }}
            </a>
            <a class="dropdown-item" href="{{ route('team.list') }}">
                <i class="fa fa-users"></i> {{ __('view_common.layout.nav.user.my_teams') }}
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                <i class="fa fa-cog"></i> {{ __('view_common.layout.nav.user.account_settings') }}
            </a>
            <div class="dropdown-divider"></div>

            <a class="dropdown-item" href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa fa-sign-out-alt"></i> {{ __('view_common.layout.nav.user.logout') }}
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                  style="display: none;">
                {{ csrf_field() }}
            </form>
        </div>
    </li>
@endguest
