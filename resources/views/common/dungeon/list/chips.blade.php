<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * Every dungeon of a seasonless game version as an abbreviation chip, grouped by selector group. The readout
 * on the left names the selected dungeon and follows hover and focus, so every abbreviation is one glance
 * from its full name - several are not unique on their own (a dungeon and its raid version share one).
 *
 * With view counts, each chip fills from the bottom up to its share of the most viewed dungeon's views, and the
 * readout puts that share into words.
 *
 * @var GameVersion                $gameVersion
 * @var Collection<int, Dungeon>   $dungeons   Sorted by selector group
 * @var Collection<string, string> $links      Keyed by dungeon key
 * @var string|null                $selected   The selected dungeon's key
 * @var Collection<int, float>     $viewShares Keyed by dungeon id, empty without view counts
 */

$viewShares ??= collect();

/** @var Collection<string, Collection<int, Dungeon>> $dungeonsByGroup */
$dungeonsByGroup = $dungeons->groupBy(static fn(Dungeon $dungeon) => $dungeon->getSelectorGroup()->value);
/** @var Dungeon|null $selectedDungeon */
$selectedDungeon = $dungeons->firstWhere('key', $selected);
// The selected dungeon can belong to another game version - the readout then invites a pick instead
$readoutName     = $selectedDungeon === null ? __('view_common.dungeon.list.choose_dungeon') : __($selectedDungeon->name);
$readoutImageUrl = $selectedDungeon?->getImageUrl() ?? $gameVersion->expansion->getWallpaperUrl();

$describeViewShare = static function (?float $viewShare): string {
    if ($viewShare === null) {
        return '';
    }

    if ($viewShare >= 1) {
        return __('view_common.dungeon.list.chips.most_viewed');
    }

    if ($viewShare <= 0) {
        return __('view_common.dungeon.list.chips.not_viewed');
    }

    // Never "0%" for a dungeon that was viewed, nor "100%" for one that is not the most viewed
    return __('view_common.dungeon.list.chips.view_share', ['percent' => min(99, max(1, (int)round($viewShare * 100)))]);
};
$readoutViews = $selectedDungeon === null ? '' : $describeViewShare($viewShares->get($selectedDungeon->id));
?>
<div class="dungeon_strip">
    <div class="dungeon_strip_readout" aria-hidden="true"
         data-name="{{ $readoutName }}" data-image="{{ $readoutImageUrl }}" data-views="{{ $readoutViews }}">
        <img class="dungeon_strip_readout_image" src="{{ $readoutImageUrl }}" alt=""/>
        <span class="dungeon_strip_readout_text">
            <span class="dungeon_strip_readout_name">{{ $readoutName }}</span>
            <span class="dungeon_strip_readout_views">{{ $readoutViews }}</span>
        </span>
    </div>
    <div class="dungeon_strip_groups" id="dungeon_strip_groups">
        @foreach($dungeonsByGroup as $group => $groupDungeons)
            <div class="dungeon_strip_group dungeon_strip_group--{{ $group }}" role="group" aria-labelledby="dungeon_strip_group_{{ $group }}">
                <span class="dungeon_strip_group_label" id="dungeon_strip_group_{{ $group }}">
                    {{ __(sprintf('view_common.dungeon.list.groups.%s', $group)) }}
                </span>
                <div class="dungeon_strip_chips">
                    @foreach($groupDungeons as $dungeon)
                        <?php
                        $isSelected = $selected === $dungeon->key;
                        $viewShare  = $viewShares->get($dungeon->id);
                        $views      = $describeViewShare($viewShare);
                        ?>
                        <a @class(['dungeon_strip_chip', 'border-accent' => $isSelected, 'dungeon_strip_chip--views' => $viewShare !== null])
                           href="{{ $links->get($dungeon->key) }}"
                           aria-label="{{ __($dungeon->name) }}" title="{{ $views === '' ? __($dungeon->name) : sprintf('%s - %s', __($dungeon->name), $views) }}"
                           data-image="{{ $dungeon->getImageUrl() }}"
                           @if($viewShare !== null) data-views="{{ $views }}" style="--dungeon-strip-view-share: {{ round($viewShare * 100, 1) }}%" @endif
                           @if($isSelected) aria-current="true" @endif>{{ __($dungeon->abbreviation) }}</a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="dungeon_strip_all" aria-expanded="false" aria-controls="dungeon_strip_groups">
        {{ __('view_common.dungeon.list.all', ['count' => $dungeons->count()]) }}
        <i class="fas fa-caret-down" aria-hidden="true"></i>
    </button>
</div>
