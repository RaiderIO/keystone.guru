<?php
$user = Auth::user();
?>
@guest
    <li class="nav-item px-3">
        <a class="btn btn-info" href="#" data-toggle="modal" data-target="#login_modal">
            <i class="fas fa-sign-in-alt"></i> {{__('Login')}}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link px-3" href="#" data-toggle="modal" data-target="#register_modal">
            {{__('Register')}}
        </a>
    </li>
@else
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            @isset($user->iconfile)
                <img src="{{ $user->iconfile->getURL() }}" alt="{{ __('Avatar') }}"
                     style="max-width: 26px; max-height: 26px"/>
            @else
                <i class="fas fa-user"></i>
            @endisset
            {{ $user->name }}
        </a>
        <div class="dropdown-menu text-center text-lg-left" aria-labelledby="navbarDropdown">
            @if( $user->hasRole('admin'))
                <a class="dropdown-item"
                   href="{{ route('dashboard.home') }}">{{__('Admin dashboard')}}</a>
                @if( env('TRACKER_ENABLED'))
                    <a class="dropdown-item"
                       href="{{ route('tracker.stats.index') }}">{{__('Admin stats')}}</a>
                @endif
                <a class="dropdown-item"
                   href="{{ route('admin.tools') }}">{{__('Admin tools')}}</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item"
                   href="{{ route('admin.releases') }}">{{__('View releases')}}</a>
                @if( $user->isAbleTo('read-expansions') )
                    <a class="dropdown-item"
                       href="{{ route('admin.expansions') }}">{{__('View expansions')}}</a>
                @endif
                @if( $user->isAbleTo('read-dungeons') )
                    <a class="dropdown-item"
                       href="{{ route('admin.dungeons') }}">{{__('View dungeons')}}</a>
                @endif
                @if( $user->isAbleTo('read-npcs') )
                    <a class="dropdown-item"
                       href="{{ route('admin.npcs') }}">{{__('View NPCs')}}</a>
                @endif
                <a class="dropdown-item"
                   href="{{ route('admin.spells') }}">{{__('View spells')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.users') }}">{{__('View users')}}</a>
                <a class="dropdown-item"
                   href="{{ route('admin.userreports') }}">{{__('View user reports') }}
                    @if($numUserReports > 0)
                        <span
                            class="badge badge-primary badge-pill">{{ $numUserReports }}</span>
                    @endif
                </a>
                <div class="dropdown-divider"></div>
            @endif
            <a class="dropdown-item" href="{{ route('profile.routes') }}">
                <i class="fa fa-route"></i> {{ __('My routes') }}
            </a>
            <a class="dropdown-item" href="{{ route('profile.tags') }}">
                <i class="fa fa-tag"></i> {{ __('My tags') }}
            </a>
            <a class="dropdown-item" href="{{ route('team.list') }}">
                <i class="fa fa-users"></i> {{ __('My teams') }}
            </a>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                <i class="fa fa-user"></i> {{ __('My profile') }}
            </a>
            <div class="dropdown-divider"></div>

            <a class="dropdown-item" href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa fa-sign-out-alt"></i> {{ __('Logout') }}
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                  style="display: none;">
                {{ csrf_field() }}
            </form>
        </div>
    </li>
@endguest