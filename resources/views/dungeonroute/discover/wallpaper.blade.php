<?php

use App\Models\Dungeon;
use App\Models\Expansion;

// @formatter:off
// ^ important - it BREAKS the display of wallpapers when the contents of url('') get a new line..
/**
 * @var Expansion|null $expansion
 * @var Dungeon|null $dungeon
 */
if( isset($dungeon) || isset($expansion) ) {
?>
<div class="dungeon_wallpaper"
     @if(isset($dungeon))
         @if(!$dungeon->hasImageWallpaper())
            style="background-image: url('{{ $dungeon->getImageWallpaperUrl() }}')">
         @else
             style="background-image:
            url('{{ $dungeon->getImageWallpaperUrl() }}')">
         @endif
     @elseif(isset($expansion))
        style="background-image: url('{{ $expansion->getWallpaperUrl() }}')">
     @else
    @endif
</div>
<div class="dungeon_wallpaper_cover">
</div>
<?php
}

// @formatter:on
