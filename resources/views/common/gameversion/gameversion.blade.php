<?php
/**
 * @var \App\Models\GameVersion\GameVersion $gameVersion
 * @var int|null $width
 * @var bool $showName
 */
$width    ??= null;
$showName ??= false;
?>
<img src="{{ asset(sprintf('images/gameversions/%s.png', $gameVersion->key)) }}" alt="{{ __($gameVersion->name) }}"
     @isset($width) width="{{ $width }}px" @endisset height="17px"/> {{ $showName ? __($gameVersion->name) : '' }}
