<?php
/** @var \Illuminate\Support\Collection|\App\Models\Expansion[] $activeExpansions */
/** @var \App\Models\Season $currentSeason */

$navs = [
    route('dungeonroutes.search') => [
        'fa'   => 'fas fa-search',
        'text' => __('views/common.layout.header.search')
    ]
];

$expansionRoutes = [];
foreach ($activeExpansions as $expansion) {
    $expansionRoutes[route('dungeonroutes.expansion', ['expansion' => $expansion])] =
        sprintf('<img src="%s" alt="%s" style="width: 50px"/> %s',
            url(sprintf('images/expansions/%s.png', $expansion->shortname),
            ),
            __($expansion->name),
//            $expansion->hasTimewalkingEvent() ?
//                __('views/common.layout.header.routes_timewalking', ['expansion' => __($expansion->name)]) :
                __('views/common.layout.header.routes', ['expansion' => __($expansion->name)])
        );
}
$navs[route('dungeonroutes.season', ['expansion' => $currentSeason->expansion, 'season' => $currentSeason->index])] = [
    'text' => $currentSeason->name
];

$navs[__('Expansion routes')] = $expansionRoutes;

$navs[route('misc.affixes')] = [
    'text' => __('views/common.layout.header.affixes')
];

?>
<div class="navbar-top-fixed-spacer"></div>
<nav
    class="navbar fixed-top navbar-expand-lg {{ $theme === 'lux' ? 'navbar-light' : 'navbar-dark' }} bg-header"
    data-toggle="navbar-shrink">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img src="{{ url('/images/logo/logo_and_text.png') }}" alt="{{ config('app.name') }}"
                 height="44px;" width="200px;">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse"
                data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="{{ __('views/common.layout.header.toggle_navigation_title') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse text-center text-lg-left" id="mainNavbar">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item px-3">
                    <a class="btn btn-accent" href="#"
                       data-toggle="modal" data-target="#create_route_modal">
                        <i class="fas fa-plus"></i> {{__('views/common.layout.header.create_route')}}
                    </a>
                </li>
                @foreach($navs as $route => $opts)
                    @if($opts === 'divider')
                        <li class="nav-item nav-item-divider"></li>
                    @elseif(filter_var($route, FILTER_VALIDATE_URL) !== false)
                        <li class="nav-item">
                            <a class="nav-link pr-3 {{ strpos(Request::url(), $route) === 0 ? 'active' : '' }}"
                               href="{{ $route }}">
                                @isset($opts['fa'])
                                    <i class="{{ $opts['fa'] }}"></i>
                                @endisset
                                {{ $opts['text'] }}
                                @if(isset($opts['new']) && $opts['new'])
                                    <sup class="text-success">{{ __('views/common.layout.header.new') }}</sup>
                                @endif
                            </a>
                        </li>
                    @else
                        <?php
                        /** @noinspection PhpUndefinedVariableInspection */
                        $headerText = $route;
                        $dropdownId = \Illuminate\Support\Str::slug($headerText)
                        ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="{{ $dropdownId }}" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ $headerText }}
                            </a>
                            <div class="dropdown-menu" aria-labelledby="{{ $dropdownId }}">
                                @foreach($opts as $route => $text)
                                    <a class="dropdown-item" href="{{ $route }}">{!! $text !!}</a>
                                @endforeach
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item nav-item-divider"></li>
                @include('vendor.language.flags')
                @include('common.layout.navuser')
                @include('common.layout.navthemeswitch')
            </ul>
        </div>
    </div>
</nav>
