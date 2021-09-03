@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('views/misc.credits.title')])

@section('header-title', __('views/misc.credits.header'))

@section('content')
    <p>
        This website could not exist without the help of talented developers around the world, offering their hard work
        for free for usage in projects such as these. The following packages/images deserve credit for being integrated
        in this website:
    </p>

    <h2>{{ __('views/misc.credits.header_people') }}</h2>
    <p>
    <ul>
        <li>
            <span class="font-weight-bold">Daenarian</span> for helping map the first part of The Motherlode
            meticulously.
        </li>
        <li>
            <span class="font-weight-bold">Sonskyn</span> and <span class="font-weight-bold">Doug</span> for making good
            use of the site and providing me with valuable feedback across the board to greatly improve the website.
            From bug reports to feature requests to general feedback and dungeon mapping, I owe you big time!
        </li>
        <li>
            Various members of
            <span class="font-weight-bold">{{ link_to('http://darkwolves.eu/', 'Dark Wolves') }}</span> and
            <span class="font-weight-bold">Sark</span>
            for helping me with mapping dungeons, from odd requests to "kill that mob" to "hold up for a second you
            guys are going too fast" :).
        </li>
    </ul>
    </p>


    <h2 class="mt-4">{{ __('views/misc.credits.header_libraries') }}</h2>
    <h4>
        General
    </h4>
    <p>
    <ul>
        <li>{{ link_to('https://datatables.net/', 'Datatables') }}</li>
        <li>{{ link_to('https://getbootstrap.com/', 'Bootstrap 4') }}</li>
    </ul>
    </p>
    <h4>
        Map technology
    </h4>
    <p>
    <ul>
        <li>{{ link_to('https://leafletjs.com', 'Leaflet') }}</li>
        <li>{{ link_to('https://leaflet.github.io/Leaflet.draw/', 'Leaflet Draw') }}</li>
        <li>{{ link_to('https://github.com/aratcliffe/Leaflet.contextmenu', 'Leaflet Context Menu') }}</li>
        <li>{{ link_to('https://github.com/bbecquet/Leaflet.PolylineDecorator', 'Leaflet Polyline Decorator') }}</li>
        <li>{{ link_to('https://github.com/maxogden/geojson-js-utils', 'Geojson Utils') }}</li>
    </ul>
    </p>
    <h4>
        Server-side
    </h4>
    <p>
    <ul>
        <li>{{ link_to('https://laravel.com/', 'Laravel') }}</li>
    </ul>
    </p>

    <h2 class="mt-4">{{ __('views/misc.credits.header_images') }}</h2>
    <h4>
        Alliance & Horde icons
    </h4>
    <p>
        {{ link_to('https://www.deviantart.com/atriace/art/Alliance-Horde-Faction-Logos-193328658', 'atriace on DeviantArt') }}
    </p>

    <h4>
        Crossed swords icon
    </h4>
    <p>
        {{ link_to('https://thenounproject.com/term/crossed-swords/152699/', 'The Noun Project') }}
    </p>

    <h4>
        Image upscaling
    </h4>
    <p>
        {{ link_to('https://topazlabs.com/gigapixel-ai/', 'Topaz Gigapixel AI') }}
    </p>

    <h4>
        In-game icon pack
    </h4>
    <p>
        {{ link_to('https://barrens.chat/viewtopic.php?f=5&t=63&p=1726#p1726', 'barrens.chat') }}
    </p>

    <h4>
        Radio icons
    </h4>
    <p>
        {{ link_to('https://loading.io/spinner/radio') }}
    </p>

    <h4>
        Enemy type icons
    </h4>
    <p>
    <ul>
        <li>
            Aberration:
            {{ link_to('https://www.iconfinder.com/icons/3759345/events_eyes_halloween_horror_october_witches_zombie_icon') }}
        </li>
        <li>
            Beast: {{ link_to('https://www.iconfinder.com/icons/1604346/animal_footprint_paw_pet_print_track_icon') }}
        </li>
        <li>
            Critter: {{ link_to('https://www.iconfinder.com/icons/586373/easter_easter_bunny_easter_hare_easter_rabbit_rabbit_icon') }}
        </li>
        <li>
            Demon: {{ link_to('https://www.iconfinder.com/icons/725403/alchemy_antichrist_halloween_hell_pentagram_wicca_witch_icon') }}
        </li>
        <li>
            Dragonkin: {{ link_to('https://www.iconfinder.com/icons/2580985/dragon_fairytale_fantasy_flying_head_magic_monster_icon') }}
        </li>
        <li>
            Elemental: {{ link_to('https://www.iconfinder.com/icons/1161512/earth_element_elemental_magic_rock_spell_stone_icon') }}
        </li>
        <li>
            Giant: {{ link_to('https://www.iconfinder.com/icons/2117581/fantasy_folklore_giant_monster_ogre_troll_icon') }}
        </li>
        <li>
            Humanoid:
            <div>Icon made by <a href="https://www.flaticon.com/authors/freepik" title="Freepik">Freepik</a> from <a
                    href="https://www.flaticon.com/" title="Flaticon">www.flaticon.com</a> is licensed by <a
                    href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0"
                    target="_blank" rel="noopener noreferrer">CC 3.0 BY</a></div>
        </li>
        <li>
            Mechanical: {{ link_to('https://www.iconfinder.com/icons/2376279/android_bot_droid_robot_icon') }}
        </li>
        <li>
            Undead: {{ link_to('https://www.iconfinder.com/icons/1974373/halloween_hand_rise_zombie_icon') }}
        </li>
        <li>
            Void:
            {{ link_to('https://www.iconfinder.com/icons/3903825/frozen_theme_snowflake_swirl_snowflake_tornado_swirl_snowflake_winter_swirl_icon') }}
        </li>
        <li>
            Tide: {{ link_to('https://www.iconfinder.com/icons/4605982/big_ocean_sea_tide_wave_icon') }}
        </li>
    </ul>
    </p>

    <h4>
        Truesight icon
    </h4>
    <p>
    <ul>
        <li>
            {{ link_to('https://www.iconfinder.com/icons/2724129/eye_face_human_vision_icon_icon') }}
        </li>
    </ul>
    </p>

    <h4>
        Comment/question mark icon
    </h4>
    <p>
    <ul>
        <li>
            <div>Icons made by <a href="https://www.flaticon.com/authors/freepik" title="Freepik">Freepik</a> from <a
                    href="https://www.flaticon.com/" title="Flaticon">www.flaticon.com</a></div>
        </li>
    </ul>
    </p>

@endsection