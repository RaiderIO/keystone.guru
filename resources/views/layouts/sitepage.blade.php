<?php
/** @var $menuModels \Illuminate\Database\Eloquent\Model[] */
/** @var $viewName string */
/** @var $isProduction string */
/** @var $isMobile boolean */
/** @var $nameAndVersion string */
/** @var $theme string */
/** @var $menuModelEdit \Illuminate\Database\Eloquent\Model */

$user = \Illuminate\Support\Facades\Auth::user();
// Custom content or not
$custom = $custom ?? false;
// Wide mode or not (only relevant if custom = false)
$wide = $wide ?? false;
// Show header or not
$header = $header ?? true;
// Show footer or not
$footer = $footer ?? true;
// Show ads if not set
$showAds = $showAds ?? true;
// Any class to add to the root div
$rootClass = $rootClass ?? '';
// Page title
$title = $title ?? null;
// Breadcrumbs
$breadcrumbs       = $breadcrumbs ?? $viewName;
$breadcrumbsParams = $breadcrumbsParams ?? [];
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
        <div class="container container_wide mt-4 mb-4 {{$rootClass}}">

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

        @if (!$isProduction && (!Auth::check() || !$user->hasRole('admin')))
            <div class="container-fluid alert alert-warning text-center mt-4">
                <i class="fa fa-exclamation-triangle"></i>
                {{ __('views/layouts.sitepage.staging_banner_description') }}
                <br>
                <a href="https://keystone.guru/">{{ __('views/layouts.sitepage.staging_banner_take_me_away') }}</a>
            </div>
        @endif

        @yield('global-message')

        <div
            class="container-fluid mb-4 {{$rootClass}} {{ $wide ? "flex-fill pl-lg-3 pr-lg-3" : "col-md-8 offset-md-2" }}">

            @include('common.layout.breadcrumbs', ['breadcrumbs' => $breadcrumbs, 'breadcrumbsParams' => $breadcrumbsParams])

            @if( !$adFree && $showAds && !$isMobile)
                <div align="center" class="my-4">
                    @include('common.thirdparty.adunit', ['id' => 'site_top_header', 'type' => 'header', 'reportAdPosition' => 'top-right'])
                </div>
            @endif

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

            @if( !$adFree && $showAds )
                <div align="center" class="mt-4">
                    @include('common.thirdparty.adunit', ['id' => 'site_bottom_header', 'type' => 'footer'])
                </div>
            @endif
        </div>
    @endif

    @if($footer)
        @include('common.layout.footer')
    @endif

    @if($header)
        <!-- Modal create route -->
        @component('common.general.modal', ['id' => 'create_route_modal', 'size' => 'xl'])
            @include('common.modal.createroute')
        @endcomponent
        <!-- END modal create route -->
    @endif

@endsection
