<?php

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Service\Expansion\ExpansionService;
use Illuminate\Support\Collection;

/**
 * @var ExpansionService    $expansionService
 * @var Expansion           $expansion
 * @var Collection<Dungeon> $dungeons
 * @var string|null         $route
 * @var callable|null       $subtextFn
 */

$dungeons ??= $expansion->dungeonsAndRaids()->active()->get();
$colCount = 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$test       ??= false;
$names      ??= true;
$links      ??= collect();
$route      ??= null;
$selectable ??= false;
$subtextFn  ??= null;

// @formatter:off
for( $i = 0; $i < $rowCount; ++$i ) { ?>
<div class="row no-gutters">
    <?php
    for( $j = 0; $j < $colCount; ++$j ) {
        $index = $i * $colCount + $j;
        if( $dungeons->has($index) ){
            /** @var Dungeon $dungeon */
            $dungeon = $dungeons->get($index);
            $link = $links->firstWhere('dungeon', $dungeon->key);
            ?>
            <div
                class="grid_dungeon col-lg-{{ 12 / $colCount }} col-{{ 12 / ($colCount / 2) }} p-2 {{$selectable ? 'selectable' : ''}}"
                data-id="{{ $dungeon->id }}">
                <div class="card-img-caption">
                    @isset($link)
                    <a href="{{ $link['link'] }}">
                        @endisset
                        @if($names)
                            <h5 class="card-text text-white">
                                {{ __($dungeon->name) }}
                            </h5>
                        @endif

                        @isset($subtextFn)
                        <div class="card-text subtext text-white">
                            {!! $subtextFn($dungeon) !!}
                        </div>
                        @endisset

                        <img class="card-img-top"
                             src="{{ $dungeon->getImageUrl() }}"
                             style="width: 100%" alt="{{ __($dungeon->name) }}"/>
                        @isset($link)
                    </a>
                @endisset
                </div>
            </div>
        <?php
        }
    }

    // @formatter:on
        ?>
</div>
<?php }
