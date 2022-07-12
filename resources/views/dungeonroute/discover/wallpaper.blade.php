<?php
// @formatter:off
// ^ important - it BREAKS the display of wallpapers when the contents of url('') get a new line..
/** @var \App\Models\Expansion|null $expansion */
/** @var \App\Models\Dungeon|null $dungeon */
if( isset($dungeon) || isset($expansion) ) {
?>
<div class="dungeon_wallpaper"
     @if(isset($dungeon))
         @if(!$dungeon->hasImageWallpaper() && $dungeon->expansion->shortname === \App\Models\Expansion::EXPANSION_LEGION)
            style="background-image: url('{{ url(sprintf('/images/dungeons/%s/wallpaper.jpg', $dungeon->expansion->shortname)) }}')">
         @else
             style="background-image:
            url('{{ $dungeon->getImageWallpaperUrl() }}')">
         @endif
     @elseif(isset($expansion))
        style="background-image: url('{{ url(sprintf('/images/dungeons/%s/wallpaper.jpg', $expansion->shortname)) }}')">
     @else
    @endif
</div>
<div class="dungeon_wallpaper_cover">
</div>
<?php
}
// @formatter:on
?>
