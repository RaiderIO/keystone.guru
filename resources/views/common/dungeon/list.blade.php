<?php

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\Expansion\ExpansionService;
use Illuminate\Support\Collection;

/**
 * @var GameVersion              $gameVersion
 * @var Expansion                $expansion
 * @var Collection<int, Dungeon> $dungeons
 * @var boolean                  $useAbbreviation
 * @var Season|null              $nextSeason
 * @var string|null              $nextSeasonLink
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
// The upcoming season is shown as an extra card next to the dungeons, leading to a selection of just
// its dungeons - it never takes the place of the current season's dungeons (#3761). Only set when the
// season is close enough to its start to be advertised.
$nextSeason     ??= null;
$nextSeasonLink ??= null;
// Ease tiers ("what's easy this week") - a Collection<affixGroupId, Collection<dungeonId, tier>>
$easeTiers         ??= collect();
$currentAffixGroup ??= null;

// @formatter:off
?>
<div class="row">
    <?php
    $hasSelectedDungeon = false;
    foreach($dungeons->take($maxColCount) as $dungeon) {
        /** @var Dungeon $dungeon */
        $hasSelectedDungeon = $hasSelectedDungeon || $selected === $dungeon->key;
        $thisWeekTier = $currentAffixGroup === null ? null : ($easeTiers[$currentAffixGroup->id][$dungeon->id] ?? null);
        ?>
        @include('common.dungeon.list.card', [
            'id' => $dungeon->id,
            'link' => $links->get($dungeon->key),
            'title' => $useAbbreviation ? __($dungeon->abbreviation) : __($dungeon->name),
            'isSelected' => $selected === $dungeon->key,
            'imageUrl' => $dungeon->getImageUrl(),
            'imageAlt' => __($dungeon->name),
            'width' => $width,
            'thisWeekTier' => $thisWeekTier,
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
            // Explicitly null: an @include inherits the enclosing scope, so the last dungeon's tier would
            // otherwise leak into this card - it is not a dungeon and has no difficulty tier
            'thisWeekTier' => null,
        ])
    <?php
    }

    if($nextSeason !== null && $nextSeasonLink !== null) {
        ?>
        @include('common.dungeon.list.card', [
            'link' => $nextSeasonLink,
            'title' => __('view_common.dungeon.list.next_season'),
            'isSelected' => false,
            'imageUrl' => $nextSeason->expansion->getWallpaperUrl(),
            'imageAlt' => __($nextSeason->expansion->name),
            'width' => $width,
            'thisWeekTier' => null,
        ])
    <?php
    }
// @formatter:on
    ?>
</div>
