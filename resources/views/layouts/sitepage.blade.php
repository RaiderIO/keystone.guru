<?php

use App\Models\Laratrust\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @var Model[]     $menuModels
 * @var string      $viewName
 * @var string      $isProduction
 * @var boolean     $isMobile
 * @var string      $nameAndVersion
 * @var string      $theme
 * @var Model       $menuModelEdit
 * @var string|null $messageBanner
 */

$user = Auth::user();
// Custom content or not
$custom ??= false;
// Wide mode or not (only relevant if custom = false)
$wide ??= false;
// Show header or not
$header ??= true;
// Show footer or not
$footer ??= true;
// Show ads if not set
$showAds ??= true;
// Any class to add to the root div
$rootClass                 ??= '';
$disableDefaultRootClasses ??= false;
// Page title
$title ??= null;
// Breadcrumbs
$breadcrumbs       ??= $viewName ?? 'home';
$breadcrumbsParams ??= [];
?>
@extends('layouts.app', ['title' => $title])

@section('head')
    @parent

    @if($header)
        @include('common.general.inline', ['path' => 'common/general/navbarshrink'])
    @endif
@endsection

@section('app-content')

    @if($header)
        @include('common.layout.header')
    @endif

    @if($custom)
        @empty($rootClass)
            @yield('content')
        @else
            <div class="{{$rootClass}}">
                @yield('content')
            </div>
        @endisset

    @elseif(isset($menuItems))
        <div class="container container_wide mb-4 {{$rootClass}}">

            @include('common.layout.breadcrumbs', ['breadcrumbs' => $breadcrumbs, 'breadcrumbsParams' => $breadcrumbsParams])

            <div class="row">
                <div class="col-xl-3 bg-secondary p-3">
                    <h4>{{ $menuTitle }}</h4>
                    <hr>
                    @isset($menuModels)
                        <select id="selected_model_id" class="form-control selectpicker">
                            @foreach($menuModels as $menuModel)
                                @php($hasIcon = isset($menuModel->iconfile))
                                <option
                                    data-url="{{ route($menuModelsRoute, [$menuModelsRouteParameterName => $menuModel->getRouteKey()]) }}"
                                    @if($hasIcon)
                                        data-content="<img src='{{ $menuModel->iconfile->getURL() }}' style='max-height: 16px;'/> {{ $menuModel->name }}"
                                    @endif
                                    {{ $menuModelEdit->getKey() === $menuModel->getKey() ? 'selected' : '' }}
                                >{{ $hasIcon ? '' : $menuModel->name }}</option>
                            @endforeach
                        </select>
                        <hr>
                    @endisset
                    <ul class="nav flex-column nav-pills">
                        @foreach($menuItems as $index => $menuItem)
                            <li class="nav-item">
                                <a class="nav-link {{ $index === 0 ? 'active' : '' }}"
                                   data-toggle="tab" href="{{ $menuItem['target'] }}" role="tab"
                                   aria-controls="routes" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                    <i class="fas {{ $menuItem['icon'] }}"></i> {{ $menuItem['text'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-xl-9 bg-secondary ml-0 mt-xl-0 mt-3 p-3">
                    @yield('content')
                </div>
            </div>
        </div>

    @else

        @if (!$isProduction && (!Auth::check() || !$user->hasRole(Role::ROLE_ADMIN)))
            @component('common.layout.messagebanner')
                <i class="fa fa-exclamation-triangle"></i>
                {{ __('view_layouts.sitepage.staging_banner_description') }}
                <br>
                <a href="https://keystone.guru/">{{ __('view_layouts.sitepage.staging_banner_take_me_away') }}</a>
            @endcomponent
        @endif

        @if($messageBanner !== null)
            @component('common.layout.messagebanner')
                {!! $messageBanner !!}
            @endcomponent
        @endif

        <div
            class="container-fluid mb-4 {{$rootClass}} {{ $wide ? "flex-fill pl-lg-3 pr-lg-3" : ($disableDefaultRootClasses ? "" :  "col-md-8 offset-md-2") }}">

            @include('common.layout.breadcrumbs', ['breadcrumbs' => $breadcrumbs, 'breadcrumbsParams' => $breadcrumbsParams])

            @hasSection('header-title')
                <div class="row my-4">
                    @hasSection('header-addition')
                        <div class="col text-center">
                            <h4>@yield('header-title')</h4>
                        </div>
                        <div class="ml-auto">
                            @yield('header-addition')
                        </div>
                    @else
                        <div class="col-lg-12 text-center">
                            <h4>@yield('header-title')</h4>
                        </div>
                    @endif
                </div>
            @endif

            @include('common.general.messages')

            @yield('content')
        </div>
    @endif

    @if($footer)
        @include('common.layout.footer')
    @endif

    @if($header)
        @component('common.general.modal', ['id' => 'create_route_modal', 'size' => 'xl'])
            @include('common.modal.createroute')
        @endcomponent

        @component('common.general.modal', ['id' => 'upload_logs_modal', 'size' => 'lg'])
            @include('common.modal.uploadlogs')
        @endcomponent
    @endif

@endsection
