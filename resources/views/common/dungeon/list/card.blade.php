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
?>
<div
    class="list_dungeon col p-1 selectable {{ $isSelected ? 'selected border-accent' : '' }} {{$width ?? 'col'}}"
    @isset($id)
        data-id="{{ $id }}"
    @endisset
>
    <div class="card-img-caption">
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
