<?php
/** @var $isProduction string */
/** @var $version string */
/** @var $theme string */
/** @var $hasNewChangelog bool */
/** @var $latestRelease \App\Models\Release */
/** @var $latestReleaseSpotlight \App\Models\Release */

// Show ads or not
$showAds = $showAds ?? true;
$user = \Illuminate\Support\Facades\Auth::user();
// Show the legal modal or not if people didn't agree to it yet
$showLegalModal = $showLegalModal ?? true;
$showSpotlight = $showSpotlight ?? true;
// Setup the title
$title = isset($title) ? $title . ' - ' : '';
// Any additional parameters to pass to the login/register blade
$loginParams = $loginParams ?? [];
$registerParams = $registerParams ?? [];
// Show cookie consent
$cookieConsent = $cookieConsent ?? true;
// If user already approved of the cookie..
if ($cookieConsent && isset($_COOKIE['cookieconsent_status']) && $_COOKIE['cookieconsent_status'] === 'dismiss') {
    // Don't bother the user with it anymore
    $cookieConsent = false;
}
$devCacheBuster = config('app.env') === 'local' ? '?t=' . time() : '';
// Analytics or not, default = $isProduction
$analytics = $analytics ?? $isProduction;

$bodyClass = $bodyClass ?? '';
$rootClass = $rootClass ?? '';

// Bit of a hack to do this here - but for now this works
$showSpotlightRelease = false;
if ($showSpotlight && $latestReleaseSpotlight instanceof \App\Models\Release) {
    // Only if the user hasn't seen the latest spotlight release yet
    $showSpotlightRelease = ($_COOKIE['changelog_release'] ?? 0) < $latestReleaseSpotlight->id;

    // It's now at least the release of the latest spotlight release since that's what's pushed in your face atm
    if ($showSpotlightRelease) {
        setcookie('changelog_release', $latestReleaseSpotlight->id);
    }
}
?><!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="theme {{$theme}}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @hasSection('linkpreview')
        @yield('linkpreview')
    @endif

    @sectionMissing('linkpreview')
        @include('common.general.linkpreview', [
            'title' => __('views/layouts.app.linkpreview_title')
        ])
    @endif

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title . config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app-' . $version . '.css') . $devCacheBuster }}" rel="stylesheet">
    <link href="{{ asset('css/custom-' . $version . '.css') . $devCacheBuster }}" rel="stylesheet">
    {{--    <link href="{{ asset('css/lib-' . $version . '.css') . $devCacheBuster }}" rel="stylesheet">--}}
    <link href="{{ asset('css/theme-' . $version . '.css') . $devCacheBuster }}" rel="stylesheet">
    <link href="{{ asset('css/home-' . $version . '.css') . $devCacheBuster }}" rel="stylesheet">
    <link rel="icon" href="{{ url("/images/icon/favicon.ico") }}">
    @yield('head')

    @include('common.general.inlinemanager')
    @include('common.general.inline', ['path' => 'layouts/app', 'section' => false, 'options' => ['guest' => Auth::guest()]])
    @include('common.general.sitescripts', ['showLegalModal' => $showLegalModal])

    @isset($menuItems)
        @include('common.general.inline', ['path' => 'common/general/menuitemsanchor'])
    @endisset

    @if($cookieConsent)
        @include('common.thirdparty.cookieconsent')
    @endif

    @if(!$adFree && $showAds)
        @include('common.thirdparty.ads')
    @endif
    @if($analytics)
        @include('common.thirdparty.analytics')
    @endif
</head>
<body class="{{ $bodyClass }}">
<div id="app">
    @yield('app-content')
</div>

@auth
    @if(!$user->legal_agreed)
        @component('common.general.modal', ['id' => 'legal_modal', 'static' => true])
            @include('common.modal.legal')
        @endcomponent
    @endif
@endauth

@guest
    <!-- Modal login -->
    @component('common.general.modal', ['id' => 'login_modal', 'class' => 'modal-dialog-small'])
        @include('common.forms.login', array_merge(['modal' => true], $loginParams))
    @endcomponent
    <!-- END modal login -->

    <!-- Modal register -->
    @component('common.general.modal', ['id' => 'register_modal', 'class' => 'modal-dialog-small register-modal-dialog'])
        @include('common.forms.register', array_merge(['modal' => true], $registerParams))
    @endcomponent
    <!-- END modal register -->
@endguest

@if($showSpotlight && $showSpotlightRelease)
    @component('common.general.modal', ['id' => 'new_release_modal', 'active' => true])
        @include('common.release.release', ['release' => $latestReleaseSpotlight])
    @endcomponent
@endif

<!-- Scripts -->
<script src="{{ asset('js/app-' . $version . '.js') . $devCacheBuster }}"></script>
<?php // Compiled only in production, otherwise include all files as-is to prevent having to recompile everything all the time ?>
<script src="{{ asset('js/custom-' . $version . '.js') .$devCacheBuster }}"></script>
<script src="{{ asset('js/lib-' . $version . '.js') . $devCacheBuster }}"></script>
@yield('scripts')
<script type="application/javascript">
    $(function () {
        // Do this once and not a bunch of times for all different elements
        refreshSelectPickers();
        // All layers have been fetched and everything rebuilt, refresh tooltips for all elements
        refreshTooltips();
    });
</script>
</body>
</html>
