<?php

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Service\Expansion\ExpansionService;
use Illuminate\Support\Collection;

/**
 * @var GameVersion         $gameVersion
 * @var Expansion           $expansion
 * @var Collection<int, Dungeon> $dungeons
 * @var boolean             $useAbbreviation
 */

$dungeons        ??= $expansion->dungeonsAndRaids()->active()->get();
$useAbbreviation ??= false;
$names           ??= true;
$links           ??= collect();
$selectable      ??= false;
$selected        ??= null;
$subtextFn       ??= null;
$width           ??= null;
$showMore        ??= false;
$maxColCount     ??= 8;

// @formatter:off
?>
<div class="row">
    <?php
    $hasSelectedDungeon = false;
    foreach($dungeons->take($maxColCount) as $dungeon) {
        /** @var Dungeon $dungeon */
        $hasSelectedDungeon = $hasSelectedDungeon || $selected === $dungeon->key;
        ?>
        @include('common.dungeon.list.card', [
            'id' => $dungeon->id,
            'link' => $links->get($dungeon->key),
            'title' => $useAbbreviation ? __($dungeon->abbreviation) : __($dungeon->name),
            'isSelected' => $selected === $dungeon->key,
            'imageUrl' => $dungeon->getImageUrl(),
            'imageAlt' => __($dungeon->name),
            'width' => $width,
        ])
    <?php
    }

    if($showMore && $dungeons->count() >= $maxColCount) {
        ?>
        @include('common.dungeon.list.card', [
            'link' => $links->get('more'),
            'title' => 'More',
            'isSelected' => !$hasSelectedDungeon,
            'imageUrl' => $gameVersion->expansion->getWallpaperUrl(),
            'imageAlt' => __($gameVersion->expansion->name),
            'width' => $width,
        ])
    <?php
    }
// @formatter:on
    ?>
</div>
