@inject('seasonService', 'App\Service\Season\SeasonService')
@inject('subcreationTierListService', 'App\Service\Subcreation\AffixGroupEaseTierServiceInterface')

<?php
/** @var $expansion \App\Models\Expansion */
/** @var $dungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection */
/** @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup|null */
/** @var $nextAffixGroup \App\Models\AffixGroup\AffixGroup|null */

$colCount = $colCount ?? 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$names = $names ?? true;
$links = $links ?? collect();

$sideOffset = $colCount === 3 ? 1 : 0;

for( $i = 0; $i < $rowCount; $i++ ) { ?>
<div class="row no-gutters">
    <?php for( $j = 0; $j < $colCount; $j++ ) {
    $index = $i * $colCount + $j;
    if( $dungeons->has($index) ){
    /** @var \App\Models\Dungeon $dungeon */
    $dungeon = $dungeons->get($index);
    $link = $links->where('dungeon', $dungeon->key)->first();
    ?>
    <div
        class="p-2 col-lg-3 {{ $sideOffset && ($j === 0) ? 'ml-lg-auto' : (($j === $colCount - 1) ? 'mr-lg-auto' : '') }}">
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
                    <div class="d-lg-inline d-none">
                        <p class="card-text text-center">
                            <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                {{ __('views/common.dungeon.griddiscover.popular') }}
                            </a>

                            &middot;

                            @if($currentAffixGroup !== null)
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
                                    'tier' => optional($tiers->get($currentAffixGroup->id)->where('dungeon_id', $dungeon->id)->first())->tier
                                ])
                                @endif
                                {!! ($thisWeekTier = ob_get_clean()) !!}

                                &middot;
                            @else
                                <span class="text-muted">
                                    {{ __('views/common.dungeon.griddiscover.this_week') }}
                                </span>
                            @endif

                            @if($nextAffixGroup !== null)
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
                                    'tier' => optional($tiers->get($nextAffixGroup->id)->where('dungeon_id', $dungeon->id)->first())->tier
                                ])
                                @endif
                                {!! ($nextWeekTier = ob_get_clean()) !!}

                                &middot;
                            @else
                                <span class="text-muted">
                                    {{ __('views/common.dungeon.griddiscover.next_week') }}
                                </span>
                            @endif

                            <a href="{{ route('dungeonroutes.discoverdungeon.new', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                {{ __('views/common.dungeon.griddiscover.new') }}
                            </a>
                        </p>
                    </div>

                    <!-- Mobile view -->
                    <div class="row no-gutters card-text text-center d-lg-none">
                        <div class="col">
                            <h4>
                                <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                    {{ __('views/common.dungeon.griddiscover.popular') }}
                                </a>
                            </h4>
                        </div>
                        <div class="col">
                            <h4>
                                @isset($thisWeekTier)
                                    <a href="{{ route('dungeonroutes.discoverdungeon.thisweek', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                        {{ __('views/common.dungeon.griddiscover.this_week') }}
                                    </a>
                                    {!! $thisWeekTier !!}
                                @else
                                    <span class="text-muted">
                                        {{ __('views/common.dungeon.griddiscover.this_week') }}
                                    </span>
                                @endisset
                            </h4>
                        </div>
                    </div>
                    <div class="row no-gutters card-text text-center d-lg-none">
                        <div class="col">
                            <h4>
                                <a href="{{ route('dungeonroutes.discoverdungeon.new', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                    {{ __('views/common.dungeon.griddiscover.new') }}
                                </a>
                            </h4>
                        </div>
                        <div class="col">
                            <h4>
                                @isset($nextWeekTier)
                                    <a href="{{ route('dungeonroutes.discoverdungeon.nextweek', ['expansion' => $dungeon->expansion, 'dungeon' => $dungeon->slug]) }}">
                                        {{ __('views/common.dungeon.griddiscover.next_week') }}
                                    </a>
                                    {!! $nextWeekTier !!}
                                @else
                                    <span class="text-muted">
                                        {{ __('views/common.dungeon.griddiscover.next_week') }}
                                    </span>
                                @endisset
                            </h4>
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

