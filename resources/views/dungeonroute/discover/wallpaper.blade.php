<?php

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;

// @formatter:off
// ^ important - it BREAKS the display of wallpapers when the contents of url('') get a new line..
/**
 * @var GameVersion|null $gameVersion
 * @var Expansion|null $expansion
 * @var Dungeon|null $dungeon
 */

$wallpaperUrl = null;

if (isset($dungeon)) {
    $wallpaperUrl = $dungeon->getImageWallpaperUrl();
} elseif (isset($expansion)) {
    $wallpaperUrl = $expansion->getWallpaperUrl();
} elseif (isset($gameVersion->expansion)) {
    $wallpaperUrl = $gameVersion->expansion->getWallpaperUrl();
}

if ($wallpaperUrl !== null) {
?>
<div class="dungeon_wallpaper" style="background-image: url('{{ $wallpaperUrl }}')"></div>
<div class="dungeon_wallpaper_cover"></div>
<?php
}

// @formatter:on
