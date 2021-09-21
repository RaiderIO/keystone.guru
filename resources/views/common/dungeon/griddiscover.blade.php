@inject('seasonService', 'App\Service\Season\SeasonService')
@inject('subcreationTierListService', 'App\Service\Subcreation\AffixGroupEaseTierServiceInterface')

<?php
/** @var $subcreationTierListService \App\Service\Subcreation\AffixGroupEaseTierServiceInterface */
/** @var $seasonService \App\Service\Season\SeasonService */
/** @var $expansion \App\Models\Expansion */
/** @var $dungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection */
$colCount = 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$names = $names ?? true;
$links = $links ?? collect();

$currentSeason = $seasonService->getCurrentSeason();
$currentAffixGroup = $currentSeason->getCurrentAffixGroup();
$nextAffixGroup = $currentSeason->getNextAffixGroup();

$tiers = $subcreationTierListService->getTiersByAffixGroups(collect([
    $currentAffixGroup,
    $nextAffixGroup
]));

for( $i = 0; $i < $rowCount; $i++ ) { ?>
<div class="row no-gutters">
    <?php for( $j = 0; $j < $colCount; $j++ ) {
    $index = $i * $colCount + $j;
    if( $dungeons->has($index) ){
    /** @var \App\Models\Dungeon $dungeon */
    $dungeon = $dungeons->get($index);
    $link = $links->where('dungeon', $dungeon->key)->first();
    ?>
    <div class="p-2 col-lg-{{ 12 / $colCount }} col-{{ 12 / ($colCount / 2) }} ">
        <div class="card">
            <div class="card-img-caption">
                <a href="{{ route('dungeonroutes.discoverdungeon', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                    <h5 class="card-text text-white">
                        {{ __($dungeon->name) }}
                    </h5>
                    <img class="card-img-top"
                         src="{{ url(sprintf('images/dungeons/%s/%s.jpg', $dungeon->expansion->shortname, $dungeon->key)) }}"
                         style="width: 100%; height: 100%" alt="{{ __($dungeon->name) }}"/>
                </a>
            </div>
            @if($names)
                <div class="card-body">
                    <!-- Normal big screen view -->
                    <div class="d-xl-inline d-none">
                        <p class="card-text text-center">
                            <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                {{ __('views/common.dungeon.griddiscover.popular') }}
                            </a>

                            &middot;

                            <?php $url = route('dungeonroutes.discoverdungeon.thisweek', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]); ?>
                            <a href="{{ $url }}">
                                {{ __('views/common.dungeon.griddiscover.this_week') }}
                            </a>
                            <?php ob_start() ?>
                            @if($tiers->has($currentAffixGroup->id))
                            @include('common.dungeonroute.tier', [
                                'dungeon' => $dungeon,
                                'affixgroup' => $currentAffixGroup,
                                'url' => $url,
                                'tier' => $tiers->get($currentAffixGroup->id)->where('dungeon_id', $dungeon->id)->first()->tier
                            ])
                            @endif
                            {!! ($thisWeekTier = ob_get_clean()) !!}

                            &middot;

                            <?php $url = route('dungeonroutes.discoverdungeon.nextweek', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]); ?>
                            <a href="{{ $url }}">
                                {{ __('views/common.dungeon.griddiscover.next_week') }}
                            </a>
                            <?php ob_start() ?>
                            @if($tiers->has($nextAffixGroup->id))
                            @include('common.dungeonroute.tier', [
                                'dungeon' => $dungeon,
                                'affixgroup' => $nextAffixGroup,
                                'url' => $url,
                                'tier' => $tiers->get($nextAffixGroup->id)->where('dungeon_id', $dungeon->id)->first()->tier
                            ])
                            @endif
                            {!! ($nextWeekTier = ob_get_clean()) !!}

                            &middot;

                            <a href="{{ route('dungeonroutes.discoverdungeon.new', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                {{ __('views/common.dungeon.griddiscover.new') }}
                            </a>
                        </p>
                    </div>

                    <!-- Mobile view -->
                    <div class="row no-gutters card-text text-center d-xl-none">
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                {{ __('views/common.dungeon.griddiscover.popular') }}
                            </a>
                        </div>
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.thisweek', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                {{ __('views/common.dungeon.griddiscover.this_week') }}
                            </a>
                            {!! $thisWeekTier !!}
                        </div>
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.nextweek', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                {{ __('views/common.dungeon.griddiscover.next_week') }}
                            </a>
                            {!! $nextWeekTier !!}
                        </div>
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.new', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                {{ __('views/common.dungeon.griddiscover.new') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <?php
    }
    } ?>
</div>
<?php } ?>

