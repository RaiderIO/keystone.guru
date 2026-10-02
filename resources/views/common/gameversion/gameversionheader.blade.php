<?php

use App\Models\GameVersion\GameVersion;

/**
 * @var GameVersion $currentUserGameVersion
 * @var GameVersion $gameVersion
 */
$isSelectedGameVersion = $currentUserGameVersion->id === $gameVersion->id;
?>
<li>
    <a class="game_version {{ $isSelectedGameVersion ? 'border-accent' : '' }}"
       href="{{ route('gameversion.update', ['gameVersion' => $gameVersion]) }}"
       @if($isSelectedGameVersion) aria-current="true" @endif>
        <img src="{{ ksgAssetImage(sprintf('gameversions/%s.webp', $gameVersion->key)) }}" alt="" height="16"/>
        {{ __($gameVersion->name) }}
    </a>
</li>
