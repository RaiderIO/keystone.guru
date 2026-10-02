<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * Every dungeon of a seasonless game version as an abbreviation chip, grouped by selector group. The readout
 * on the left names the selected dungeon and follows hover and focus, so every abbreviation is one glance
 * from its full name - several are not unique on their own (a dungeon and its raid version share one).
 *
 * @var GameVersion                $gameVersion
 * @var Collection<int, Dungeon>   $dungeons Sorted by selector group
 * @var Collection<string, string> $links    Keyed by dungeon key
 * @var string|null                $selected The selected dungeon's key
 * @var Collection<int, int>       $popularDungeonIds
 */

/** @var Collection<string, Collection<int, Dungeon>> $dungeonsByGroup */
$dungeonsByGroup = $dungeons->groupBy(static fn(Dungeon $dungeon) => $dungeon->getSelectorGroup()->value);
/** @var Dungeon|null $selectedDungeon */
$selectedDungeon = $dungeons->firstWhere('key', $selected);
// The selected dungeon can belong to another game version - the readout then invites a pick instead
$readoutName     = $selectedDungeon === null ? __('view_common.dungeon.list.choose_dungeon') : __($selectedDungeon->name);
$readoutImageUrl = $selectedDungeon?->getImageUrl() ?? $gameVersion->expansion->getWallpaperUrl();
?>
<div class="dungeon_strip">
    <div class="dungeon_strip_readout" aria-hidden="true"
         data-name="{{ $readoutName }}" data-image="{{ $readoutImageUrl }}">
        <img class="dungeon_strip_readout_image" src="{{ $readoutImageUrl }}" alt=""/>
        <span class="dungeon_strip_readout_name">{{ $readoutName }}</span>
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
                        $isPopular  = $popularDungeonIds->contains($dungeon->id);
                        ?>
                        <a @class(['dungeon_strip_chip', 'border-accent' => $isSelected, 'dungeon_strip_chip--popular' => $isPopular])
                           href="{{ $links->get($dungeon->key) }}"
                           aria-label="{{ __($dungeon->name) }}" title="{{ $isPopular ? __('view_common.dungeon.list.chips.popular_title', ['name' => __($dungeon->name)]) : __($dungeon->name) }}"
                           data-image="{{ $dungeon->getImageUrl() }}"
                           @if($isPopular) aria-describedby="dungeon_strip_popular" @endif
                           @if($isSelected) aria-current="true" @endif>{{ __($dungeon->abbreviation) }}</a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @if($popularDungeonIds->isNotEmpty())
        <span class="visually-hidden" id="dungeon_strip_popular">{{ __('view_common.dungeon.list.chips.popular') }}</span>
    @endif
    <button type="button" class="dungeon_strip_all" aria-expanded="false" aria-controls="dungeon_strip_groups">
        {{ __('view_common.dungeon.list.all', ['count' => $dungeons->count()]) }}
        <i class="fas fa-caret-down" aria-hidden="true"></i>
    </button>
</div>
