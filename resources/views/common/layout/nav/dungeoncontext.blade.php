<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * This is only visible for mobile users.
 *
 * The desktop dungeon context is a strip of image cards and chips that cannot lay out at phone widths, so it
 * is hidden below `lg`. This bottom sheet carries the same selection, the same per-page links and the same
 * upcoming-season entry, with the game version switch on top. siteheader.js moves it to the end of <body>:
 * the sticky header is a stacking context, and Bootstrap's backdrop would otherwise cover the sheet.
 *
 * @var GameVersion                              $gameVersion
 * @var Collection<int, Dungeon>                 $dungeons
 * @var Dungeon|null                             $selectedDungeon Null when the saved dungeon is not listed
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
$showGroupHeaders = !$gameVersion->has_seasons;
/** @var Collection<string, Collection<int, Dungeon>> $dungeonsByGroup */
$dungeonsByGroup = $dungeons->groupBy(static fn(Dungeon $dungeon) => $dungeon->getSelectorGroup()->value);

$filterText = static fn(string ...$texts): string => mb_strtolower(implode(' ', $texts));
?>
<div class="offcanvas offcanvas-bottom dungeon_sheet d-lg-none" id="dungeon_sheet" tabindex="-1"
     aria-labelledby="dungeon_sheet_title">
    <div class="offcanvas-header dungeon_sheet_header">
        <h2 class="offcanvas-title dungeon_sheet_title" id="dungeon_sheet_title">
            {{ __('view_common.layout.nav.dungeoncontext.change_dungeon') }}
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                aria-label="{{ __('view_common.layout.nav.dungeoncontext.close') }}"></button>
    </div>
    <div class="offcanvas-body dungeon_sheet_body">
        @include('common.layout.nav.gameversions', ['currentUserGameVersion' => $gameVersion])
        <div class="dungeon_sheet_filter" role="search">
            <i class="fas fa-search dungeon_sheet_filter_icon" aria-hidden="true"></i>
            <input type="search" class="form-control dungeon_sheet_filter_input"
                   placeholder="{{ __('view_common.layout.nav.dungeoncontext.filter_placeholder') }}"
                   aria-label="{{ __('view_common.layout.nav.dungeoncontext.filter_label') }}"
                   aria-controls="dungeon_sheet_list" autocomplete="off" spellcheck="false" enterkeyhint="go"/>
        </div>
        <div class="dungeon_sheet_list" id="dungeon_sheet_list">
            @foreach($dungeonsByGroup as $group => $groupDungeons)
                <section class="dungeon_sheet_group"
                         @if($showGroupHeaders) aria-labelledby="dungeon_sheet_group_{{ $group }}" @endif>
                    @if($showGroupHeaders)
                        <h3 class="dungeon_sheet_group_label" id="dungeon_sheet_group_{{ $group }}">
                            {{ __(sprintf('view_common.dungeon.list.groups.%s', $group)) }}
                        </h3>
                    @endif
                    <ul class="dungeon_sheet_rows">
                        @foreach($groupDungeons as $dungeon)
                            <?php
                            $isSelected   = $selectedDungeon?->key === $dungeon->key;
                            $thisWeekTier = $currentAffixGroup === null ? null : ($easeTiers[$currentAffixGroup->id][$dungeon->id] ?? null);
                            ?>
                            <li>
                                <a @class(['dungeon_sheet_row', 'border-accent' => $isSelected])
                                   href="{{ $links->get($dungeon->key) }}"
                                   data-filter-text="{{ $filterText(__($dungeon->name), __($dungeon->abbreviation)) }}"
                                   @if($isSelected) aria-current="true" @endif>
                                    <img class="dungeon_sheet_row_image" src="{{ $dungeon->getImageUrl() }}"
                                         loading="lazy" alt="" data-image-fallback/>
                                    <span class="dungeon_sheet_row_name">{{ __($dungeon->name) }}</span>
                                    @if($thisWeekTier !== null)
                                        {{-- Part of the link's name: a title inside a link reaches neither a keyboard nor a screen reader --}}
                                        <span class="dungeon_sheet_row_tier">
                                            <span class="tier {{ strtolower($thisWeekTier) }}" aria-hidden="true">{{ $thisWeekTier }}</span>
                                            <span class="visually-hidden">{{ __('view_common.dungeon.list.card.this_week_tier_label', ['tier' => $thisWeekTier]) }}</span>
                                        </span>
                                    @endif
                                    <span class="dungeon_sheet_row_abbreviation" aria-hidden="true">{{ __($dungeon->abbreviation) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
            {{-- The upcoming season is advertised next to the dungeons, never in their place --}}
            @if($nextSeason !== null && $nextSeasonLink !== null)
                <section class="dungeon_sheet_group dungeon_sheet_group--next_season">
                    <ul class="dungeon_sheet_rows">
                        <li>
                            <a class="dungeon_sheet_row" href="{{ $nextSeasonLink }}"
                               data-filter-text="{{ $filterText(__('view_common.dungeon.list.next_season'), __($nextSeason->expansion->name)) }}">
                                <img class="dungeon_sheet_row_image"
                                     src="{{ $nextSeason->expansion->getWallpaperUrl() }}" loading="lazy" alt="" data-image-fallback/>
                                <span class="dungeon_sheet_row_name">{{ __('view_common.dungeon.list.next_season') }}</span>
                                <span class="dungeon_sheet_row_abbreviation" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                            </a>
                        </li>
                    </ul>
                </section>
            @endif
            <p class="dungeon_sheet_empty" hidden>{{ __('view_common.layout.nav.dungeoncontext.no_results') }}</p>
        </div>
    </div>
</div>
