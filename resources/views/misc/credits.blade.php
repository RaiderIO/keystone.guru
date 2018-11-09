@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Credits')])

@section('header-title', __('Credits'))

@section('content')
    <p>
        This website could not exist without the help of talented developers around the world, offering their hard work
        for free for usage in projects such as these. The following packages/images deserve credit for being integrated
        in this website:
    </p>

    <h2>{{ __('People') }}</h2>
    <p>
    <ul>
        <li>
            <span class="font-weight-bold">Daenarian</span> for helping map the first part of The Motherlode
            meticulously.
        </li>
        <li>
            <span class="font-weight-bold">Forced</span> for helping map bosses.
        </li>
        <li>
            Various members of
            <span class="font-weight-bold">{{ link_to('http://darkwolves.eu/', 'Dark Wolves') }}</span> and
            <span class="font-weight-bold">Sark</span>
            for helping me with mapping dungeons, from odd requests to "kill that mob" to "hold up for a second you
            guys are going to fast" :).
        </li>
    </ul>
    </p>


    <h2 class="mt-4">{{ __('Libraries') }}</h2>
    <p>
    <h4>
        General
    </h4>
    <ul>
        <li>{{ link_to('https://datatables.net/', 'Datatables') }}</li>
        <li>{{ link_to('https://getbootstrap.com/', 'Bootstrap 4') }}</li>
    </ul>
    <h4>
        Map technology
    </h4>
    <ul>
        <li>{{ link_to('https://leafletjs.com', 'Leaflet') }}</li>
        <li>{{ link_to('http://leaflet.github.io/Leaflet.draw/', 'Leaflet Draw') }}</li>
        <li>{{ link_to('https://github.com/aratcliffe/Leaflet.contextmenu', 'Leaflet Context Menu') }}</li>
        <li>{{ link_to('https://github.com/bbecquet/Leaflet.PolylineDecorator', 'Leaflet Polyline Decorator') }}</li>
        <li>{{ link_to('https://github.com/maxogden/geojson-js-utils', 'Geojson Utils') }}</li>
    </ul>
    <h4>
        Server-side
    </h4>
    <ul>
        <li>{{ link_to('https://laravel.com/', 'Laravel') }}</li>
    </ul>
    </p>

    <h2 class="mt-4">{{ __('Images') }}</h2>
    <p>
    <h4>
        Alliance & Horde icons
    </h4>
    {{ link_to('https://www.deviantart.com/atriace/art/Alliance-Horde-Faction-Logos-193328658', 'atriace on DeviantArt') }}
    </p>

    <p>
    <h4>
        Crossed swords icon
    </h4>
    {{ link_to('https://thenounproject.com/term/crossed-swords/152699/', 'The Noun Project') }}
    </p>

    <p>
    <h4>
        Image upscaling
    </h4>
    {{ link_to('http://a-sharper-scaling.com/', 'Steffen Garlach - A Sharper Scaling') }}
    </p>

    <p>
    <h4>
        In-game icon pack
    </h4>
    {{ link_to('https://barrens.chat/viewtopic.php?f=5&t=63&p=1726#p1726', 'barrens.chat') }}
    </p>


@endsection