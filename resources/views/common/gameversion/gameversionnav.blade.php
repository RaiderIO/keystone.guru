<?php

use App\Models\GameVersion\GameVersion;

/**
 * @var GameVersion $gameVersion
 * @var int|null    $width
 * @var bool        $showName
 */

$width    ??= null;
$showName ??= false;

$name = __($gameVersion->name);
?>
<img class="game_version_logo" src="{{ ksgAssetImage(sprintf('gameversions/%s.webp', $gameVersion->key)) }}"
     alt="{{ $showName ? '' : $name }}"
     @isset($width) width="{{ $width }}" @endisset
     height="17" loading="lazy"/>
{{ $showName ? $name : '' }}
