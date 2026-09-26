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
<img src="{{ ksgAssetImage(sprintf('gameversions/%s.webp', $gameVersion->key)) }}"
     alt="{{ $name }}"
     @isset($width) width="{{ $width }}px" @endisset
     height="17px" loading="lazy"/>
{{ $showName ? $name : '' }}
