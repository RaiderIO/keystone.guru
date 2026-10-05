<?php

use App\Models\Dungeon;
use App\Models\DungeonSelectorGroup;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Every dungeon of a seasonless game version as an abbreviation chip, grouped by selector group. The readout
 * on the left names the selected dungeon and follows hover and focus, so every abbreviation is one glance
 * from its full name - several are not unique on their own (a dungeon and its raid version share one).
 *
 * With view counts, each chip carries a bar under its label as long as its share of the most viewed dungeon's views,
 * and the readout puts that share into words.
 *
 * Within a group the chips follow their abbreviation rather than the full name the list arrives sorted by: the
 * abbreviation is all a chip shows, and full names put "The Deadmines" (DM) between ST and STOCK. Natural order,
 * so AQ20 comes before AQ40.
 *
 * @var GameVersion                $gameVersion
 * @var Collection<int, Dungeon>   $dungeons   Sorted by selector group
 * @var Collection<string, string> $links      Keyed by dungeon key
 * @var string|null                $selected   The selected dungeon's key
 * @var Collection<int, float>     $viewShares Keyed by dungeon id, empty without view counts
 */

$viewShares ??= collect();

/** @var Collection<string, Collection<int, Dungeon>> $dungeonsByGroup */
$dungeonsByGroup = $dungeons
    ->groupBy(static fn(Dungeon $dungeon) => $dungeon->getSelectorGroup()->value)
    ->map(static fn(Collection $groupDungeons) => $groupDungeons->sortBy([
        static fn(Dungeon $a, Dungeon $b) => strnatcasecmp(Str::ascii(__($a->abbreviation)), Str::ascii(__($b->abbreviation))),
        static fn(Dungeon $a, Dungeon $b) => strcasecmp(Str::ascii(__($a->name)), Str::ascii(__($b->name))),
    ])->values());
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

    // Never "0%" for a dungeon that was viewed, nor "100%" for one that is not the most viewed - dungeonstrip.js words it the same way
    return __('view_common.dungeon.list.chips.view_share', ['percent' => min(99, max(1, (int)round($viewShare * 100)))]);
};
// Compact mode has no room for the group names, so an icon keeps the groups apart at a glance
$getGroupIcon = static fn(string $group): string => match (DungeonSelectorGroup::from($group)) {
    DungeonSelectorGroup::WORLD   => 'fa-globe',
    DungeonSelectorGroup::DUNGEON => 'fa-dungeon',
    DungeonSelectorGroup::RAID    => 'fa-dragon',
};
$readoutViewShare = $selectedDungeon === null ? null : $viewShares->get($selectedDungeon->id);
$readoutViews     = $describeViewShare($readoutViewShare);
$filterLabel      = __('view_common.dungeon.list.chips.filter');
// Spans rather than a <kbd>, which Bootstrap's tooltip sanitizer strips
$filterTooltip = sprintf('%s<span class="draw_tool_hotkeys"><span class="draw_tool_keycap">/</span></span>', e($filterLabel));
?>
<div class="dungeon_strip">
    <div class="dungeon_strip_readout" aria-hidden="true"
         data-name="{{ $readoutName }}" data-image="{{ $readoutImageUrl }}"
         @if($readoutViewShare !== null) data-view-share="{{ round($readoutViewShare, 4) }}" @endif>
        <img class="dungeon_strip_readout_image" src="{{ $readoutImageUrl }}" alt="" data-image-fallback/>
        <span class="dungeon_strip_readout_text">
            <span class="dungeon_strip_readout_name">{{ $readoutName }}</span>
            <span class="dungeon_strip_readout_views">{{ $readoutViews }}</span>
        </span>
    </div>
    <button type="button" class="dungeon_strip_search" aria-expanded="false" aria-controls="dungeon_strip_groups"
            aria-label="{{ $filterLabel }}" aria-keyshortcuts="/"
            data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" title="{{ $filterTooltip }}">
        <i class="fas fa-search" aria-hidden="true"></i>
    </button>
    <div class="dungeon_strip_groups" id="dungeon_strip_groups">
        {{-- Only shown in the unfolded strip, which the search button and "/" open --}}
        <div class="dungeon_strip_filter" role="search">
            <i class="fas fa-search dungeon_strip_filter_icon" aria-hidden="true"></i>
            <input type="search" class="form-control dungeon_strip_filter_input"
                   placeholder="{{ __('view_common.layout.nav.dungeoncontext.filter_placeholder') }}"
                   aria-label="{{ __('view_common.layout.nav.dungeoncontext.filter_label') }}"
                   aria-keyshortcuts="/" autocomplete="off" spellcheck="false"/>
            <kbd class="dungeon_strip_filter_key" aria-hidden="true">/</kbd>
        </div>
        @foreach($dungeonsByGroup as $group => $groupDungeons)
            <div class="dungeon_strip_group dungeon_strip_group--{{ $group }}" role="group" aria-labelledby="dungeon_strip_group_{{ $group }}">
                <span class="dungeon_strip_group_label" title="{{ __(sprintf('view_common.dungeon.list.groups.%s', $group)) }}">
                    <i class="fas {{ $getGroupIcon($group) }} dungeon_strip_group_icon" aria-hidden="true"></i>
                    <span class="dungeon_strip_group_name" id="dungeon_strip_group_{{ $group }}">{{ __(sprintf('view_common.dungeon.list.groups.%s', $group)) }}</span>
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
                           @if($viewShare !== null) data-view-share="{{ round($viewShare, 4) }}" style="--dungeon-strip-view-share: {{ round($viewShare * 100, 1) }}%" @endif
                           @if($isSelected) aria-current="true" @endif>{{ __($dungeon->abbreviation) }}</a>
                    @endforeach
                </div>
            </div>
        @endforeach
        <p class="dungeon_strip_filter_empty" role="status" hidden>{{ __('view_common.layout.nav.dungeoncontext.no_results') }}</p>
    </div>
    <button type="button" class="dungeon_strip_all" aria-expanded="false" aria-controls="dungeon_strip_groups">
        {{ __('view_common.dungeon.list.all', ['count' => $dungeons->count()]) }}
        <i class="fas fa-caret-down" aria-hidden="true"></i>
    </button>
</div>
