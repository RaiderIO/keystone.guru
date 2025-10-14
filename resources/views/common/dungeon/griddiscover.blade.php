@inject('seasonService', \App\Service\Season\SeasonServiceInterface::class)
@inject('subcreationTierListService', \App\Service\AffixGroup\AffixGroupEaseTierServiceInterface::class)
<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var GameVersion $gameVersion
 * @var Season|null $season
 * @var Collection<Dungeon> $dungeons
 * @var AffixGroup|null $currentAffixGroup
 * @var AffixGroup|null $nextAffixGroup
 */

$colCount ??= 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$names ??= true;
$links ??= collect();

$sideOffset = $colCount === 3 ? 1 : 0;

for ($i = 0;
     $i < $rowCount;
     ++$i) { ?>
<div class="row no-gutters">
        <?php
    for ($j = 0;
         $j < $colCount;
         ++$j) {
        $index = $i * $colCount + $j;
    if ($dungeons->has($index)){
        /** @var Dungeon $dungeon */
        $dungeon = $dungeons->get($index);
        $link    = $links->where('dungeon', $dungeon->key)->first();
        ?>
    <div
        class="p-2 col-lg-3 {{ $sideOffset && ($j === 0) ? 'ml-lg-auto' : (($j === $colCount - 1) ? 'mr-lg-auto' : '') }}">
        <div class="card">
            <div class="card-img-caption">
                <a href="{{ route('dungeonroutes.discoverdungeon', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
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
                            <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                {{ __('view_common.dungeon.griddiscover.popular') }}
                            </a>

                            &middot;

                            @if($season !== null)
                                @if($currentAffixGroup !== null)
                                        <?php $url = route('dungeonroutes.discoverdungeon.thisweek', [
                                        'gameVersion' => $gameVersion,
                                        'dungeon'     => $dungeon->slug
                                    ]); ?>
                                    <a href="{{ $url }}">
                                        {{ __('view_common.dungeon.griddiscover.this_week') }}
                                    </a>
                                        <?php ob_start() ?>
                                    @if($tiers->has($currentAffixGroup->id))
                                        @include('common.dungeonroute.tier', [
                                            'dungeon' => $dungeon,
                                            'affixgroup' => $currentAffixGroup,
                                            'url' => $url,
                                            'tier' => optional($tiers->get($currentAffixGroup->id)->where('dungeon_id', $dungeon->id)->first())->tier
                                        ])
                                    @endif
                                    {!! ($thisWeekTier = ob_get_clean()) !!}
                                @else
                                    <span class="text-muted">
                                        {{ __('view_common.dungeon.griddiscover.this_week') }}
                                    </span>
                                    @endif

                                    &middot;

                                    @if($nextAffixGroup !== null)
                                            <?php $url = route('dungeonroutes.discoverdungeon.nextweek', [
                                            'gameVersion' => $gameVersion,
                                            'dungeon'     => $dungeon->slug
                                        ]); ?>
                                        <a href="{{ $url }}">
                                            {{ __('view_common.dungeon.griddiscover.next_week') }}
                                        </a>
                                            <?php ob_start() ?>
                                        @if($tiers->has($nextAffixGroup->id))
                                            @include('common.dungeonroute.tier', [
                                                'dungeon' => $dungeon,
                                                'affixgroup' => $nextAffixGroup,
                                                'url' => $url,
                                                'tier' => optional($tiers->get($nextAffixGroup->id)->where('dungeon_id', $dungeon->id)->first())->tier
                                            ])
                                        @endif
                                        {!! ($nextWeekTier = ob_get_clean()) !!}
                                    @else
                                        <span class="text-muted">
                                        {{ __('view_common.dungeon.griddiscover.next_week') }}
                                    </span>
                                        @endif

                                        &middot;
                                    @endif

                                    <a href="{{ route('dungeonroutes.discoverdungeon.new', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                        {{ __('view_common.dungeon.griddiscover.new') }}
                                    </a>
                        </p>
                    </div>

                    <!-- Mobile view -->
                    <div class="row no-gutters card-text text-center d-lg-none">
                        <div class="col">
                            <h4>
                                <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                    {{ __('view_common.dungeon.griddiscover.popular') }}
                                </a>
                            </h4>
                        </div>
                        @if($season !== null)
                            <div class="col">
                                <h4>
                                    @isset($thisWeekTier)
                                        <a href="{{ route('dungeonroutes.discoverdungeon.thisweek', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                            {{ __('view_common.dungeon.griddiscover.this_week') }}
                                        </a>
                                        {!! $thisWeekTier !!}
                                    @else
                                        <span class="text-muted">
                                            {{ __('view_common.dungeon.griddiscover.this_week') }}
                                        </span>
                                    @endisset
                                </h4>
                            </div>
                        @endif
                    </div>
                    <div class="row no-gutters card-text text-center d-lg-none">
                        <div class="col">
                            <h4>
                                <a href="{{ route('dungeonroutes.discoverdungeon.new', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                    {{ __('view_common.dungeon.griddiscover.new') }}
                                </a>
                            </h4>
                        </div>
                        <div class="col">
                            @if($season !== null)
                                <h4>
                                    @isset($nextWeekTier)
                                        <a href="{{ route('dungeonroutes.discoverdungeon.nextweek', ['gameVersion' => $gameVersion, 'dungeon' => $dungeon->slug]) }}">
                                            {{ __('view_common.dungeon.griddiscover.next_week') }}
                                        </a>
                                        {!! $nextWeekTier !!}
                                    @else
                                        <span class="text-muted">
                                            {{ __('view_common.dungeon.griddiscover.next_week') }}
                                        </span>
                                    @endisset
                                </h4>
                            @endif
                        </div>
                    </div>
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
?>

