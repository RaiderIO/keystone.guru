@inject('seasonService', \App\Service\Season\SeasonServiceInterface::class)
@inject('subcreationTierListService', \App\Service\AffixGroup\AffixGroupEaseTierServiceInterface::class)
<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var GameVersion                                                       $gameVersion
 * @var Season|null                                                       $season
 * @var Collection<Dungeon>                                               $dungeons
 * @var AffixGroup|null                                                   $currentAffixGroup
 * @var AffixGroup|null                                                   $nextAffixGroup
 * @var Collection<string, Collection<array{href: string, text: string}>> $links
 */

$colCount ??= 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$names ??= true;
$links ??= collect();

$sideOffset = $colCount === 3 ? 1 : 0;

// @formatter:off
for ($i = 0; $i < $rowCount; ++$i) { ?>
<div class="row no-gutters">
<?php
    for ($j = 0; $j < $colCount; ++$j) {
        $index = $i * $colCount + $j;
        if ($dungeons->has($index)){
            /** @var Dungeon $dungeon */
            $dungeon = $dungeons->get($index);
            /** @var Collection<array{href: string, text: string}> $linksForDungeon */
            $linksForDungeon = $links->get($dungeon->key);
            ?>
        <div class="p-2 col-lg-3 {{ $sideOffset && ($j === 0) ? 'ml-lg-auto' : (($j === $colCount - 1) ? 'mr-lg-auto' : '') }}">
            <div class="card">
                <div class="card-img-caption">
                    <a href="{{ $linksForDungeon->first()['href'] }}">
                        <h5 class="card-text text-white">
                            {{ __($dungeon->name) }}
                        </h5>
                        <img class="card-img-top"
                             src="{{ $dungeon->getImageUrl() }}"
                             style="width: 100%; height: 100%" alt="{{ __($dungeon->name) }}"/>
                    </a>
                </div>
                @if($names)
                    <div class="card-body">
                        <!-- Normal big screen view -->
                        <div class="d-lg-inline d-none">
                            <p class="card-text text-center">
                                @foreach($linksForDungeon as $link)
                                    <a href="{{ $link['href'] }}">
                                        {{ $link['text'] }}
                                    </a>
                                    @if(!$loop->last)
                                        &middot;
                                    @endif
                                @endforeach
                            </p>
                        </div>

                        <?php
                        // @TODO Fix mobile view
                        ?>
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
// @formatter:on
