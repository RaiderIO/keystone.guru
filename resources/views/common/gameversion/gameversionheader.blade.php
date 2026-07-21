<?php

use App\Models\GameVersion\GameVersion;

/**
 * @var GameVersion $currentUserGameVersion
 * @var GameVersion $gameVersion
 * @var string      $iconType
 */
$isSelectedGameVersion = $currentUserGameVersion->id === $gameVersion->id;

$iconType = $isSelectedGameVersion ? 'black' : 'white';
?>
<div class="game_version col-auto px-2 m-1  {{ $isSelectedGameVersion ? 'bg-primary' : '' }}">
    <a class="{{ $isSelectedGameVersion ? 'active' : '' }}"
       href="{{ route('gameversion.update', ['gameVersion' => $gameVersion]) }}">

        <div class="row g-0">
            <div class="col-auto">
                <span class="align-middle">
                    <img src="{{ ksgAssetImage(sprintf('gameversions/logo_%s_small.webp', $iconType)) }}"
                         alt="{{ __($gameVersion->name) }}"
                         height="15px"
                         <?php // WHY ?>
                         style="top: -2px; position: relative;"/>
                </span>
            </div>
            <div class="col-auto fw-bold ps-1">
                <span class="fw-bold small align-middle">
                    {{ strtoupper(__($gameVersion->name)) }}
                </span>
            </div>
        </div>

    </a>
</div>
