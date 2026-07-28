@inject('seasonService', \App\Service\Season\SeasonServiceInterface::class)
<?php

use App\Features\DungeonRouteListRework;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

/**
 * @var GameVersion                $gameVersion
 * @var Season|null                $season
 * @var Collection<int, Dungeon>   $dungeons
 * @var Collection<string, string> $links
 */

$colCount ??= 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$names ??= true;
$links ??= collect();

$sideOffset = $colCount === 3 ? 1 : 0;

for ($i = 0; $i < $rowCount; ++$i) { ?>
<div class="row g-0">
        <?php
    for ($j = 0; $j < $colCount; ++$j) {
        $index = $i * $colCount + $j;
    if ($dungeons->has($index)){
        /** @var Dungeon $dungeon */
        $dungeon = $dungeons->get($index);
        $link    = $links->get($dungeon->key);
        ?>
    <div
        class="p-2 col-lg-3 {{ $sideOffset && ($j === 0) ? 'ms-lg-auto' : (($j === $colCount - 1) ? 'me-lg-auto' : '') }}">
        <div class="card">
            <div class="card-img-caption">
                <a href="{{ $link }}">
                    <h5 class="card-text text-white dungeon_card_dungeon_name">
                        {{ __($dungeon->name) }}
                    </h5>
                    <img class="card-img-top"
                         src="{{ $dungeon->getImageUrl() }}"
                         style="width: 100%; height: 100%" alt="{{ __($dungeon->name) }}"/>
                </a>
            </div>
            @if($names)
                <div class="card-body">
                    @if(Feature::active(DungeonRouteListRework::class))
                        <?php // The reworked discovery folds popular (and new) into the base dungeon page. ?>
                        <!-- Normal big screen view -->
                        <div class="d-lg-inline d-none">
                            <p class="card-text text-center">
                                <a href="{{ route('dungeonroutes.discoverdungeon', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                    {{ __('view_common.dungeon.griddiscover.popular') }}
                                </a>
                            </p>
                        </div>

                        <!-- Mobile view -->
                        <div class="row g-0 card-text text-center d-lg-none">
                            <div class="col">
                                <h4>
                                    <a href="{{ route('dungeonroutes.discoverdungeon', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                        {{ __('view_common.dungeon.griddiscover.popular') }}
                                    </a>
                                </h4>
                            </div>
                        </div>
                    @else
                        <!-- Normal big screen view -->
                        <div class="d-lg-inline d-none">
                            <p class="card-text text-center">
                                <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                    {{ __('view_common.dungeon.griddiscover.popular') }}
                                </a>

                                &middot;

                                <a href="{{ route('dungeonroutes.discoverdungeon.new', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                    {{ __('view_common.dungeon.griddiscover.new') }}
                                </a>
                            </p>
                        </div>

                        <!-- Mobile view -->
                        <div class="row g-0 card-text text-center d-lg-none">
                            <div class="col">
                                <h4>
                                    <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                        {{ __('view_common.dungeon.griddiscover.popular') }}
                                    </a>
                                </h4>
                            </div>
                            <div class="col">
                                <h4>
                                    <a href="{{ route('dungeonroutes.discoverdungeon.new', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                        {{ __('view_common.dungeon.griddiscover.new') }}
                                    </a>
                                </h4>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
        <?php
    }
    }
        ?>
</div>
<?php }
