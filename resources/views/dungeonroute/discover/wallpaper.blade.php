<div class="dungeon_wallpaper"
     @if($dungeon->expansion->shortname === \App\Models\Expansion::EXPANSION_LEGION)
     style="background-image: url('{{ url(sprintf('/images/dungeons/%s/wallpaper.jpg', $dungeon->expansion->shortname)) }}')">
    @else
        style="background-image:
        url('{{ url(sprintf('/images/dungeons/%s/%s_wallpaper.jpg', $dungeon->expansion->shortname, $dungeon->key)) }}
        ')">
    @endif
</div>
<div class="dungeon_wallpaper_cover">
</div>
{{--<div class="dungeon_wallpaper_gradient">--}}
{{--</div>--}}
