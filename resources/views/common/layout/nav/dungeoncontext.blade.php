<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * This is only visible for mobile users.
 *
 * The desktop dungeon context is a strip of image cards that cannot lay out at phone widths, so it
 * is hidden below `lg` - which left mobile users no way to switch dungeon at all (#4097). This
 * dropdown carries the same selection, the same per-page links and the same upcoming-season entry.
 *
 * @var GameVersion                              $gameVersion
 * @var Collection<int, Dungeon>                 $dungeons
 * @var Dungeon                                  $selectedDungeon
 * @var Collection<string, string>               $links
 * @var Season|null                              $nextSeason
 * @var string|null                              $nextSeasonLink
 * @var Collection<int, Collection<int, string>> $easeTiers
 * @var AffixGroup|null                          $currentAffixGroup
 */

$nextSeason        ??= null;
$nextSeasonLink    ??= null;
$easeTiers         ??= collect();
$currentAffixGroup ??= null;

// A seasonless game version lists every dungeon and raid it has mapped, grouped the same way as the desktop
// chip grid; a season's pool is one group and needs no header.
$showGroupHeaders   = !$gameVersion->has_seasons;
/** @var Collection<string, Collection<int, Dungeon>> $dungeonsByGroup */
$dungeonsByGroup    = $dungeons->groupBy(static fn(Dungeon $dungeon) => $dungeon->getSelectorGroup()->value);
$changeDungeonLabel = __('view_common.layout.nav.dungeoncontext.change_dungeon');
?>
<li class="nav-item dropdown dungeon_context_nav">
    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="dungeonContextDropdown"
       role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
       aria-label="{{ $changeDungeonLabel }}">
        <img class="dungeon_context_nav_icon me-2" src="{{ $selectedDungeon->getImageUrl() }}"
             alt="{{ __($selectedDungeon->name) }}"/>
        <span class="dungeon_context_nav_label text-truncate">{{ __($selectedDungeon->abbreviation) }}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end dungeon_context_nav_menu"
         aria-labelledby="dungeonContextDropdown">
        <h6 class="dropdown-header">{{ $changeDungeonLabel }}</h6>
        @foreach($dungeonsByGroup as $group => $groupDungeons)
            @if($showGroupHeaders)
                <h6 class="dropdown-header">{{ __(sprintf('view_common.dungeon.list.groups.%s', $group)) }}</h6>
            @endif
            @foreach($groupDungeons as $dungeon)
                <?php
                $thisWeekTier = $currentAffixGroup === null ? null : ($easeTiers[$currentAffixGroup->id][$dungeon->id] ?? null);
                ?>
                <a class="dropdown-item d-flex align-items-center {{ $selectedDungeon->key === $dungeon->key ? 'active' : '' }}"
                   href="{{ $links->get($dungeon->key) }}"
                   @if($selectedDungeon->key === $dungeon->key) aria-current="true" @endif>
                    <img class="dungeon_context_nav_icon me-2" src="{{ $dungeon->getImageUrl() }}" loading="lazy" alt=""/>
                    <span class="flex-grow-1 text-start">{{ __($dungeon->name) }}</span>
                    @if($thisWeekTier !== null)
                        <span class="dungeon_context_nav_tier ms-2" data-bs-toggle="tooltip"
                              title="{{ __('view_common.dungeon.list.card.this_week_tier') }}">
                            <span class="tier {{ strtolower($thisWeekTier) }}">{{ $thisWeekTier }}</span>
                        </span>
                    @endif
                </a>
            @endforeach
        @endforeach
        {{-- The upcoming season is advertised next to the dungeons, never in their place (#3761) --}}
        @if($nextSeason !== null && $nextSeasonLink !== null)
            <div class="dropdown-divider"></div>
            <a class="dropdown-item d-flex align-items-center" href="{{ $nextSeasonLink }}">
                <img class="dungeon_context_nav_icon me-2" src="{{ $nextSeason->expansion->getWallpaperUrl() }}"
                     loading="lazy" alt="{{ __($nextSeason->expansion->name) }}"/>
                <span class="flex-grow-1 text-start">{{ __('view_common.dungeon.list.next_season') }}</span>
            </a>
        @endif
    </div>
</li>
