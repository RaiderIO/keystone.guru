@inject('seasonService', 'App\Service\Season\SeasonService')

<?php
/** @var $seasonService \App\Service\Season\SeasonService */
/** @var $expansion \App\Models\Expansion */
/** @var $dungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection */
$colCount = 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$names = $names ?? true;
$links = $links ?? collect();

$currentSeason = $seasonService->getCurrentSeason();

for( $i = 0; $i < $rowCount; $i++ ) { ?>
<div class="row no-gutters">
    <?php for( $j = 0; $j < $colCount; $j++ ) {
    $index = $i * $colCount + $j;
    if( $dungeons->has($index) ){
    $dungeon = $dungeons->get($index);
    $link = $links->where('dungeon', $dungeon->key)->first();
    ?>
    <div class="p-2 col-lg-{{ 12 / $colCount }} col-{{ 12 / ($colCount / 2) }} ">
        <div class="card">
            <div class="card-img-caption">
                <a href="{{ route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon->slug]) }}">
                    <h5 class="card-text text-white">
                        {{ $dungeon->name }}
                    </h5>
                    <img class="card-img-top"
                         src="images/dungeons/{{$dungeon->expansion->shortname}}/{{ $dungeon->key }}.jpg"
                         style="width: 100%; height: 100%" alt="{{ __($dungeon->name) }}"/>
                </a>
            </div>
            @if($names)
                <div class="card-body">
                    <!-- Normal big screen view -->
                    <div class="d-xl-inline d-none">
                        <p class="card-text text-center">
                            <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['dungeon' => $dungeon->slug]) }}">
                                {{ __('Popular') }}
                            </a>

                            &middot;

                            <?php $url = route('dungeonroutes.discoverdungeon.thisweek', ['dungeon' => $dungeon->slug]); ?>
                            <a href="{{ $url }}">
                                {{ __('This week ') }}
                            </a>
                            <?php ob_start() ?>
                            @include('common.dungeonroute.tier', [
                                'dungeon' => $dungeon,
                                'affixgroup' => $currentSeason->getCurrentAffixGroup(),
                                'url' => $url,
                            ])
                            {!! ($thisWeekTier = ob_get_clean()) !!}

                            &middot;

                            <?php $url = route('dungeonroutes.discoverdungeon.nextweek', ['dungeon' => $dungeon->slug]); ?>
                            <a href="{{ $url }}">
                                {{ __('Next week ') }}
                            </a>
                            <?php ob_start() ?>
                            @include('common.dungeonroute.tier', [
                                'dungeon' => $dungeon,
                                'affixgroup' => $currentSeason->getNextAffixGroup(),
                                'url' => $url,
                            ])
                            {!! ($nextWeekTier = ob_get_clean()) !!}

                            &middot;

                            <a href="{{ route('dungeonroutes.discoverdungeon.new', ['dungeon' => $dungeon->slug]) }}">
                                {{ __('New') }}
                            </a>
                        </p>
                    </div>

                    <!-- Mobile view -->
                    <div class="row no-gutters card-text text-center d-xl-none">
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['dungeon' => $dungeon->slug]) }}">
                                {{ __('Popular') }}
                            </a>
                        </div>
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.thisweek', ['dungeon' => $dungeon->slug]) }}">
                                {{ __('This week') }}
                            </a>
                            {!! $thisWeekTier !!}
                        </div>
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.nextweek', ['dungeon' => $dungeon->slug]) }}">
                                {{ __('Next week') }}
                            </a>
                            {!! $nextWeekTier !!}
                        </div>
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.new', ['dungeon' => $dungeon->slug]) }}">
                                {{ __('New') }}
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

