@php @endphp
<?php
/**
 * @var int|null    $id
 * @var string      $link
 * @var bool        $isSelected
 * @var string      $title    The tile's label, the dungeon's abbreviation in the header
 * @var string|null $fullName Shown in place of an abbreviated title on hover and focus, and the link's only name
 * @var string      $imageUrl
 * @var string      $imageAlt
 * @var string|null $width
 */

$id           ??= null;
$fullName     ??= null;
$thisWeekTier ??= null;
?>
<div
    class="list_dungeon col selectable {{ $isSelected ? 'selected border-accent' : '' }} {{$width ?? 'col'}}"
    @isset($id)
        data-id="{{ $id }}"
    @endisset
>
    <div class="card-img-caption">
        @if($thisWeekTier !== null)
            {{-- This week's ease tier (archon.gg). Kept outside the card's <a> to avoid nesting anchors. --}}
            <div class="dungeon_card_tiers">
                {{-- Focusable so its tooltip opens for the keyboard as well, and named so a screen reader hears more than a letter --}}
                <span class="dungeon_card_tier" tabindex="0" role="img" data-bs-toggle="tooltip"
                      title="{{ __('view_common.dungeon.list.card.this_week_tier') }}"
                      aria-label="{{ __('view_common.dungeon.list.card.this_week_tier_label', ['tier' => $thisWeekTier]) }}">
                    <span class="tier {{ strtolower($thisWeekTier) }}" aria-hidden="true">{{ $thisWeekTier }}</span>
                </span>
            </div>
        @endif
        <a href="{{ $link }}" @if($isSelected) aria-current="true" @endif>
            <span class="card-text text-white dungeon_card_dungeon_name" @if($fullName !== null) aria-hidden="true" @endif>
                {{ $title }}
            </span>
            @if($fullName !== null)
                <span class="card-text text-white dungeon_card_dungeon_full_name">{{ $fullName }}</span>
            @endif

            <img class="card-img-top"
                 src="{{ $imageUrl }}"
                 alt="{{ $fullName === null ? $imageAlt : '' }}"
                 data-image-fallback
            />
        </a>
    </div>
</div>
