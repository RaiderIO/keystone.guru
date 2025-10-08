<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;

// @formatter:off
// ^ important - it BREAKS the display of wallpapers when the contents of url('') get a new line..
/**
 * @var GameVersion|null $gameVersion
 * @var Dungeon|null $dungeon
 */
if( isset($dungeon) || (isset($gameVersion->expansion)) ) {
?>
<div class="dungeon_wallpaper"
     @if(isset($dungeon))
         @if(!$dungeon->hasImageWallpaper())
            style="background-image: url('{{ $dungeon->getImageWallpaperUrl() }}')">
         @else
             style="background-image:
            url('{{ $dungeon->getImageWallpaperUrl() }}')">
         @endif
     @elseif(isset($gameVersion))
        style="background-image: url('{{ $gameVersion->expansion->getWallpaperUrl() }}')">
     @else
    @endif
</div>
<div class="dungeon_wallpaper_cover">
</div>
<?php
}

// @formatter:on
