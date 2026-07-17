@php @endphp
<?php
/**
 * @var int|null    $id
 * @var string      $link
 * @var bool        $isSelected
 * @var string      $title
 * @var string      $imageUrl
 * @var string      $imageAlt
 * @var string|null $width
 */

$id ??= null;
$thisWeekTier ??= null;
?>
<div
    class="list_dungeon col p-1 selectable {{ $isSelected ? 'selected border-accent' : '' }} {{$width ?? 'col'}}"
    @isset($id)
        data-id="{{ $id }}"
    @endisset
>
    <div class="card-img-caption">
        @if($thisWeekTier !== null)
            {{-- This week's ease tier (archon.gg). Kept outside the card's <a> to avoid nesting anchors. --}}
            <div class="dungeon_card_tiers">
                <span class="dungeon_card_tier" data-bs-toggle="tooltip"
                      title="{{ __('view_common.dungeon.list.card.this_week_tier') }}">
                    <span class="tier {{ strtolower($thisWeekTier) }}">{{ $thisWeekTier }}</span>
                </span>
            </div>
        @endif
        <a href="{{ $link }}">
            <h5 class="card-text text-white dungeon_card_dungeon_name">
                {{ $title }}
            </h5>

            {{--                @isset($subtextFn)--}}
            {{--                    <div class="card-text subtext text-white">--}}
            {{--                        {!! $subtextFn($dungeon) !!}--}}
            {{--                    </div>--}}
            {{--                @endisset--}}

            <img class="card-img-top"
                 src="{{ $imageUrl }}"
                 alt="{{ $imageAlt }}"
            />
        </a>
    </div>
</div>
