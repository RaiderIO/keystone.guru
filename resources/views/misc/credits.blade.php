@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Credits')])

@section('header-title', __('Credits'))

@section('content')
    <p>
        This website could not exist without the help of talented developers around the world, offering their hard work for
        free for usage in projects such as these. The following packages/images deserve credit for being integrated
        in this website:
    </p>

    <h2>{{ __('Packages') }}</h2>
    <p>

    </p>

    <h2 class="mt-2">{{ __('Images') }}</h2>
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