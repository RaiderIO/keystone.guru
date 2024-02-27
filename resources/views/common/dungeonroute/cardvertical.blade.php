@inject('cacheService', 'App\Service\Cache\CacheServiceInterface')

<?php
/** @var $cacheService \App\Service\Cache\CacheServiceInterface */
/** @var $dungeonroute \App\Models\DungeonRoute\DungeonRoute */
/** @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup */
/** @var $tierAffixGroup \App\Models\AffixGroup\AffixGroup|null */
/** @var $__env array */
/** @var $cache boolean */

$showAffixes      ??= true;
$showDungeonImage ??= false;

$cacheFn = static function () use ($showAffixes, $showDungeonImage, $dungeonroute, $currentAffixGroup, $tierAffixGroup, $__env) {
    $dominantAffix = 'keystone';
    if ($dungeonroute->hasUniqueAffix(\App\Models\Affix::AFFIX_FORTIFIED)) {
        $dominantAffix = strtolower(\App\Models\Affix::AFFIX_FORTIFIED);
    } else if ($dungeonroute->hasUniqueAffix(\App\Models\Affix::AFFIX_TYRANNICAL)) {
        $dominantAffix = strtolower(\App\Models\Affix::AFFIX_TYRANNICAL);
    }
    
    $seasonalAffix = $dungeonroute->getSeasonalAffix();
    if (!isset($tierAffixGroup)) {
        // Try to come up with a sensible default
        if ($dungeonroute->affixes->count() === 1) {
            $tierAffixGroup = $dungeonroute->affixes->first();
        } else {
            // If the affix list contains the current affix, we can use that to display the tier instead
            $tierAffixGroup = $currentAffixGroup === null ? null : ($dungeonroute->affixes->filter(static fn(\App\Models\AffixGroup\AffixGroup $affixGroup) => $affixGroup->id === $currentAffixGroup->id)->isNotEmpty() ? $currentAffixGroup : null);
        }
    }
    // Attempt a default value if there's only one affix set
    $tierAffixGroup        = $tierAffixGroup ?? $dungeonroute->affixes->count() === 1 ?: null;
    $enemyForcesPercentage = $dungeonroute->getEnemyForcesPercentage();
    $enemyForcesWarning    = $dungeonroute->enemy_forces < $dungeonroute->mappingVersion->enemy_forces_required || $enemyForcesPercentage >= 105;
    $activeFloors = $dungeonroute->dungeon->floorsForMapFacade(true)->get();
    $owlClass     = $dungeonroute->has_thumbnail && $activeFloors->count() > 1 ? 'multiple' : 'single';
    ob_start();
    ?>
<div class="row no-gutters m-xl-1 mx-0 my-3 card_dungeonroute vertical {{ $showDungeonImage ? 'dungeon_image' : '' }}">
    <div class="col">
        <div class="row">
            <div class="col">
                <div class="{{ $owlClass }} light-slider-container">
                    <ul class="light-slider {{ $owlClass }}">
                        @if( $dungeonroute->has_thumbnail )
                            @foreach($activeFloors as $floor)
                                <li>
                                    <img class="thumbnail"
                                         src="{{ $dungeonroute->getThumbnailUrl($floor->index) }}"
                                         style="display: {{ $loop->index === 0 ? 'block' : 'none' }}"
                                         alt="Thumbnail"/>
                                </li>
                            @endforeach
                        @else
                            <img class="dungeon" src="{{ $dungeonroute->dungeon->getImage32Url() }}"
                                 alt="Dungeon"/>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="d-flex flex-column h-100 bg-card"
                     @if($showDungeonImage)
                         style="background-image: url('{{ $dungeonroute->dungeon->getImageTransparentUrl() }}'); background-size: cover; background-position-y: center;"
                    @endif
                >
                    <div class="row no-gutters pt-2 px-2 header">
                        <div class="col">
                            <h4 class="mb-0 title">
                                <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}">
                                    {{ $dungeonroute->title }}
                                </a>
                            </h4>
                        </div>
                        @if( !$dungeonroute->mappingVersion->isLatestForDungeon() )
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle text-warning"
                                   title="{{ __('views/common.dungeonroute.card.outdated_mapping_version') }}"
                                   data-toggle="tooltip"></i>
                            </div>
                        @endif
                        @if( $showAffixes )
                            <div class="col-auto ml-1">
                                    <?php 
    ob_start();
    ?>
                                @foreach($dungeonroute->affixes as $affixgroup)
                                    <div
                                        class="row no-gutters {{ isset($currentAffixGroup) && $currentAffixGroup->id === $affixgroup->id ? 'current' : '' }}">
                                        @include('common.affixgroup.affixgroup', [
                                            'affixgroup' => $affixgroup,
                                            'showText' => false,
                                            'dungeon' => $dungeonroute->dungeon,
                                        ])
                                    </div>
                                @endforeach
                                    <?php 
    $affixes = ob_get_clean();
    ?>
                                <div class="row no-gutters" data-container="body" data-toggle="popover"
                                     data-placement="bottom"
                                     data-html="true"
                                     data-content="{{ $affixes }}" style="cursor: pointer;">
                                    <div class="col">
                                        <img class="select_icon"
                                             src="{{ url(sprintf('/images/affixes/%s.jpg', $dominantAffix)) }}"
                                             alt="Dominant affix"/>
                                    </div>
                                    @if($seasonalAffix !== null)
                                        <div class="col ml-1">
                                            <img class="select_icon"
                                                 src="{{ url(sprintf('/images/affixes/%s.jpg', strtolower($seasonalAffix))) }}"
                                                 alt="Dominant affix"/>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-auto px-1">
                                @if($tierAffixGroup !== null)
                                    <h4 class="font-weight-bold px-1 m-0">
                                        @include('common.dungeonroute.tier', ['dungeon' => $dungeonroute->dungeon, 'affixgroup' => $tierAffixGroup])
                                    </h4>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="row no-gutters px-2 pb-2 pt-1 px-md-3 flex-fill d-flex description_row">
                        <div class="col">
                            @if(empty($dungeonroute->description))
                                &nbsp;
                            @else
                                {{ $dungeonroute->description }}
                            @endif
                        </div>
                    </div>
                    <div class="row no-gutters p-2 enemy_forces">
                        <div class="col-4">
                            @if( $enemyForcesWarning )
                                <span class="text-warning"> <i class="fas fa-exclamation-triangle"></i> </span>
                            @else
                                <span class="text-success"> <i class="fas fa-check-circle"></i> </span>
                            @endif
                            {{--                            <span class="d-none d-xl-block">--}}
                            {{--                                {{ sprintf(--}}
                            {{--                                    '%s/%s (%s%%)',--}}
                            {{--                                    $dungeonroute->enemy_forces,--}}
                            {{--                                    $dungeonroute->mappingVersion->enemy_forces_required,--}}
                            {{--                                    $enemyForcesPercentage--}}
                            {{--                                    ) }}--}}
                            {{--                            </span>--}}
                            {{ sprintf('%s%%', $enemyForcesPercentage) }}
                        </div>
                        <div class="col-8">
                            @if( $dungeonroute->level_min !== config('keystoneguru.keystone.levels.min') && $dungeonroute->level_max !== config('keystoneguru.keystone.levels.max'))
                                @include('common.dungeonroute.level', ['levelMin' => $dungeonroute->level_min, 'levelMax' => $dungeonroute->level_max])
                            @endif
                        </div>
                    </div>
                    <div class="row no-gutters footer">
                        <div class="col bg-card-footer px-2 py-1">
                            <small class="text-muted">
                                {{ __('views/common.dungeonroute.card.by_author') }}
                                @include('common.user.name', ['user' => $dungeonroute->author, 'link' => true, 'showAnonIcon' => false])
                                @if( $dungeonroute->rating > 1 )
                                    -
                                    @include('common.dungeonroute.rating', ['count' => $dungeonroute->ratings->count(), 'rating' => (int) $dungeonroute->rating])
                                @endif
                                -
                                <span data-toggle="tooltip"
                                      title="{{ $dungeonroute->updated_at->toDateTimeString('minute') }}">
                            {{ sprintf(__('views/common.dungeonroute.card.updated_at'), $dungeonroute->updated_at->diffForHumans() ) }}
                        </span>
                            </small>
                        </div>
                        <div class="col-auto bg-card-footer px-2">
                            <button id="route_menu_button_{{ $dungeonroute->public_key }}"
                                    class="btn btn-sm menu_actions_btn py-1"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v text-muted"></i>
                            </button>
                            <div class="dropdown-menu"
                                 aria-labelledby="route_menu_button_{{ $dungeonroute->public_key }}">
                                <a class="dropdown-item" href="#" data-toggle="modal"
                                   data-target="#userreport_dungeonroute_modal"
                                   data-publickey="{{ $dungeonroute->public_key }}">
                                    <i class="fas fa-flag"></i> {{ __('views/common.dungeonroute.card.report') }}
                                </a>
                                @auth
                                    @if(Auth::user()->hasRole('admin'))
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item refresh_thumbnail"
                                           data-publickey="{{ $dungeonroute->public_key }}">
                                            <i class="fas fa-sync"></i> {{ __('views/common.dungeonroute.card.refresh_thumbnail') }}
                                        </a>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <?php 
    return ob_get_clean();
};

// Temp fix due to cached cards containing translations - and I don't want to show Russian translations to others at this time
$cache = false;

if ($cache) {
// Echo the result of this function
    echo $cacheService->remember(
        sprintf('view:dungeonroute_card_%d_%d_%d', (int)$showAffixes, (int)$showDungeonImage, $dungeonroute->id),
        $cacheFn,
        config('keystoneguru.view.common.dungeonroute.card.cache.ttl')
    );
} else {
    echo $cacheFn();
}
?>
