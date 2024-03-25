<?php

use App\Models\GameVersion\GameVersion;

/**
 * @var GameVersion $gameVersion
 * @var string      $iconType
 */
$iconType ??= 'black';
?>
<div class="row no-gutters">
    <div class="col-auto">
        <span class="align-middle">
            <img src="{{ asset(sprintf('images/gameversions/logo_%s_small.webp', $iconType)) }}"
                 alt="{{ __($gameVersion->name) }}"
                 height="15px"
                 <?php // WHY ?>
                 style="top: -2px; position: relative;"/>
        </span>
    </div>
    <div class="col-auto font-weight-bold pl-1">
        <span class="font-weight-bold small align-middle">
            {{ strtoupper(__($gameVersion->name)) }}
        </span>
    </div>
</div>
