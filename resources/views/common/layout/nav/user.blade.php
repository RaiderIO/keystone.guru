<?php

use App\Models\Laratrust\Role;

$user = Auth::user();
?>
@guest
    <li class="nav-item px-2">
        <a class="btn btn-info" href="#" data-bs-toggle="modal" data-bs-target="#login_modal">
            <i class="fas fa-sign-in-alt"></i> {{__('view_common.layout.nav.user.login')}}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link px-2" href="#" data-bs-toggle="modal" data-bs-target="#register_modal">
            {{__('view_common.layout.nav.user.register')}}
        </a>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="preferencesDropdown" role="button"
           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
           aria-label="{{ __('view_common.layout.nav.user.preferences') }}">
            <i class="fas fa-cog"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="preferencesDropdown">
            <h6 class="dropdown-header">{{ __('view_common.layout.nav.user.preferences') }}</h6>
            @include('common.layout.nav.preferences')
        </div>
    </li>
@else
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            @include('common.user.name', ['user' => $user, 'showRaiderIOStaffImage' => false])
        </a>
        <div class="dropdown-menu dropdown-menu-end text-center text-xl-start" aria-labelledby="navbarDropdown">
            <a class="dropdown-item" href="{{ route('profile.routes') }}">
                <i class="fa fa-route fa-fw"></i> {{ __('view_common.layout.nav.user.my_routes') }}
            </a>
            <a class="dropdown-item" href="{{ route('profile.view', ['user' => $user]) }}">
                <i class="fa fa-user fa-fw"></i> {{ __('view_common.layout.nav.user.my_profile') }}
            </a>
            <a class="dropdown-item" href="{{ route('profile.favorites') }}">
                <i class="fa fa-star fa-fw"></i> {{ __('view_common.layout.nav.user.my_favorites') }}
            </a>
            <a class="dropdown-item" href="{{ route('profile.tags') }}">
                <i class="fa fa-tag fa-fw"></i> {{ __('view_common.layout.nav.user.my_tags') }}
            </a>
            <a class="dropdown-item" href="{{ route('team.list') }}">
                <i class="fa fa-users fa-fw"></i> {{ __('view_common.layout.nav.user.my_teams') }}
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                <i class="fa fa-cog fa-fw"></i> {{ __('view_common.layout.nav.user.account_settings') }}
            </a>
            <div class="dropdown-divider"></div>
            <h6 class="dropdown-header">{{ __('view_common.layout.nav.user.preferences') }}</h6>
            @include('common.layout.nav.preferences')
            @if($user->hasRole(Role::ROLE_ADMIN))
                <div class="dropdown-divider"></div>
                <h6 class="dropdown-header">{{ __('view_common.layout.nav.user.admin') }}</h6>
                @if( config('telescope.enabled') )
                    <a class="dropdown-item"
                       href="{{ route('telescope') }}">
                        <i class="fa fa-binoculars fa-fw"></i> {{__('view_common.layout.nav.user.telescope')}}
                    </a>
                @endif
                <a class="dropdown-item"
                   href="{{ route('admin.tools') }}">
                    <i class="fa fa-hammer fa-fw"></i> {{__('view_common.layout.nav.user.tools')}}
                </a>
                <a class="dropdown-item"
                   href="{{ route('admin.expansions') }}">{{__('view_common.layout.nav.user.view_expansions')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.dungeons') }}">{{__('view_common.layout.nav.user.view_dungeons')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.affixes') }}">{{__('view_common.layout.nav.user.view_affixes')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.seasons') }}">{{__('view_common.layout.nav.user.view_seasons')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.npcs') }}">{{__('view_common.layout.nav.user.view_npcs')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.spells') }}">{{__('view_common.layout.nav.user.view_spells')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.users') }}">{{__('view_common.layout.nav.user.view_users')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.tools.patreon.grants.view') }}">{{__('view_common.layout.nav.user.view_patreon_manual_grants')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.tools.telemetry.view') }}">{{__('view_common.layout.nav.user.view_telemetry')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.dungeonroutes') }}">{{__('view_common.layout.nav.user.view_dungeonroutes')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.userreports') }}">{{__('view_common.layout.nav.user.view_user_reports') }}
                    @if($numUserReports > 0)
                        <span class="badge text-bg-warning rounded-pill">{{ $numUserReports }}</span>
                    @endif
                </a>
            @endif
            <div class="dropdown-divider"></div>

            <a class="dropdown-item" href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa fa-sign-out-alt fa-fw"></i> {{ __('view_common.layout.nav.user.logout') }}
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                  style="display: none;">
                {{ csrf_field() }}
            </form>
        </div>
    </li>
@endguest
