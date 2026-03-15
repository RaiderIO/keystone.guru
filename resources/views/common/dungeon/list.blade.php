<?php

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Service\Expansion\ExpansionService;
use Illuminate\Support\Collection;

/**
 * @var ExpansionService    $expansionService
 * @var Expansion           $expansion
 * @var Collection<Dungeon> $dungeons
 * @var callable|null       $subtextFn
 * @var boolean             $useAbbreviation
 * @var int|null            $colCount
 */

$dungeons        ??= $expansion->dungeonsAndRaids()->active()->get();
$useAbbreviation ??= false;
$names           ??= true;
$links           ??= collect();
$selectable      ??= false;
$selected        ??= null;
$subtextFn       ??= null;
$height          ??= null;

// @formatter:off
?>
<div class="row">
    <?php
    foreach($dungeons as $dungeon) {
        /** @var Dungeon $dungeon */
        $link = $links->get($dungeon->key);
        ?>
        <div
            class="list_dungeon col p-1 {{$selectable ? 'selectable' : ''}} {{ $selected === $dungeon->key ? 'selected border-accent' : '' }}"
            data-id="{{ $dungeon->id }}">
            <div class="card-img-caption">
                @isset($link)
                <a href="{{ $link }}">
                    @endisset
                    @if($names)
                        <h5 class="card-text text-white dungeon_card_dungeon_name">
                            {{ $useAbbreviation ? __($dungeon->abbreviation) : __($dungeon->name) }}
                        </h5>
                    @endif

                    @isset($subtextFn)
                    <div class="card-text subtext text-white">
                        {!! $subtextFn($dungeon) !!}
                    </div>
                    @endisset

                    <img class="card-img-top"
                         src="{{ $dungeon->getImageUrl() }}"
{{--                         style="height: 100%"--}}
{{--                         style="width: 100%; height: {{ $height ?? 'auto' }}" --}}
                         alt="{{ __($dungeon->name) }}"/>
                    @isset($link)
                </a>
            @endisset
            </div>
        </div>
    <?php
    }
// @formatter:on
    ?>
</div>
