<?php
// @formatter:off
/** @var \App\Models\Expansion|null $expansion */
/** @var \App\Models\Dungeon|null $dungeon */

// ^ important - it BREAKS the display of wallpapers when the contents of url('') get a new line..
if( isset($dungeon) || isset($expansion) ) {
?>
<div class="dungeon_wallpaper"
     @if(isset($expansion))
        style="background-image: url('{{ url(sprintf('/images/dungeons/%s/wallpaper.jpg', $expansion->shortname)) }}')">
     @elseif(isset($dungeon))
         @if($dungeon->expansion->shortname === \App\Models\Expansion::EXPANSION_LEGION)
            style="background-image: url('{{ url(sprintf('/images/dungeons/%s/wallpaper.jpg', $dungeon->expansion->shortname)) }}')">
         @else
             style="background-image:
            url('{{ url(sprintf('/images/dungeons/%s/%s_wallpaper.jpg', $dungeon->expansion->shortname, $dungeon->key)) }}')">
         @endif
     @else
    @endif
</div>
<div class="dungeon_wallpaper_cover">
</div>
<?php
}
// @formatter:on
?>
