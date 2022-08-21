@inject('cacheService', 'App\Service\Cache\CacheServiceInterface')

<?php
/** @var $cacheService \App\Service\Cache\CacheServiceInterface */
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup */
/** @var $tierAffixGroup \App\Models\AffixGroup\AffixGroup|null */
/** @var $__env array */
/** @var $cache boolean */

$showAffixes = $showAffixes ?? true;
$showDungeonImage = $showDungeonImage ?? false;

$cacheFn = function() use ($showAffixes, $showDungeonImage, $dungeonroute, $currentAffixGroup, $tierAffixGroup, $__env) {
$dominantAffix = 'keystone';
if( $dungeonroute->hasUniqueAffix(\App\Models\Affix::AFFIX_FORTIFIED) ) {
    $dominantAffix = strtolower(\App\Models\Affix::AFFIX_FORTIFIED);
} else if( $dungeonroute->hasUniqueAffix(\App\Models\Affix::AFFIX_TYRANNICAL) ) {
    $dominantAffix = strtolower(\App\Models\Affix::AFFIX_TYRANNICAL);
}
$seasonalAffix = $dungeonroute->getSeasonalAffix();

if (!isset($tierAffixGroup)) {
    // Try to come up with a sensible default
    if ($dungeonroute->affixes->count() === 1) {
        $tierAffixGroup = $dungeonroute->affixes->first();
    } else {
        // If the affix list contains the current affix, we can use that to display the tier instead
        $tierAffixGroup = $currentAffixGroup === null ? null : ($dungeonroute->affixes->filter(function (\App\Models\AffixGroup\AffixGroup $affixGroup) use ($currentAffixGroup) {
            return $affixGroup->id === $currentAffixGroup->id;
        })->isNotEmpty() ? $currentAffixGroup : null);
    }
}

// Attempt a default value if there's only one affix set
$tierAffixGroup = $tierAffixGroup ?? $dungeonroute->affixes->count() === 1 ?: null;
$enemyForcesPercentage = (int)(($dungeonroute->enemy_forces / $dungeonroute->dungeon->enemy_forces_required) * 100);
$enemyForcesWarning = $dungeonroute->enemy_forces < $dungeonroute->dungeon->enemy_forces_required || $enemyForcesPercentage >= 105;

$owlClass = $dungeonroute->has_thumbnail && $dungeonroute->dungeon->floors->count() > 1 ? 'multiple' : 'single';

ob_start(); ?>
<div class="row no-gutters m-xl-1 mx-0 my-3 card_dungeonroute {{ $showDungeonImage ? 'dungeon_image' : '' }}">
    <div class="col-xl-auto">
        <div class="{{ $owlClass }}">
            <div class="owl-carousel owl-theme {{ $owlClass }}">
                @if( $dungeonroute->has_thumbnail )
                    @foreach($dungeonroute->dungeon->floors as $floor)
                        <img class="thumbnail"
                             src="{{ url(sprintf('/images/route_thumbnails/%s_%s.png', $dungeonroute->public_key, $floor->index)) }}"/>
                    @endforeach
                @else
                    <img class="dungeon" src="{{ $dungeonroute->dungeon->getImage32Url() }}"/>
                @endif
            </div>
        </div>
    </div>
    <div class="col">
        <div class="d-flex flex-column h-100 bg-card"
             @if($showDungeonImage)
             style="background-image: url('{{ $dungeonroute->dungeon->getImageTransparentUrl() }}'); background-size: cover; background-position-y: center;"
            @endif
        >
            <div class="row no-gutters p-2 header">
                <div class="col">
                    <h4 class="mb-0">
                        <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => \Illuminate\Support\Str::slug($dungeonroute->title)]) }}">
                            {{ $dungeonroute->title }}
                        </a>
                    </h4>
                </div>
                @if( $showAffixes )
                    <div class="col-auto">
                        <?php
                        ob_start();
                        ?>
                        @foreach($dungeonroute->affixes as $affixgroup)
                            <div class="row no-gutters {{ isset($currentAffixGroup) && $currentAffixGroup->id === $affixgroup->id ? 'current' : '' }}">
                                @include('common.affixgroup.affixgroup', [
                                    'affixgroup' => $affixgroup,
                                    'showText' => false,
                                    'dungeon' => $dungeonroute->dungeon,
                                ])
                            </div>
                        @endforeach
                        <?php $affixes = ob_get_clean(); ?>
                            <div class="row no-gutters" data-container="body" data-toggle="popover" data-placement="bottom"
                                 data-html="true"
                                 data-content="{{ $affixes }}" style="cursor: pointer;">
                                <div class="col">
                                    <img class="select_icon" src="{{ url(sprintf('/images/affixes/%s.jpg', $dominantAffix)) }}"/>
                                </div>
                                @if($seasonalAffix !== null)
                                    <div class="col ml-1">
                                        <img class="select_icon" src="{{ url(sprintf('/images/affixes/%s.jpg', strtolower($seasonalAffix))) }}"/>
                                    </div>
                                @endif
                            </div>
                    </div>
                    <div class="col-auto px-1">
                        @if($tierAffixGroup !== null)
                            <h4 class="font-weight-bold px-1">
                                @include('common.dungeonroute.tier', ['dungeon' => $dungeonroute->dungeon, 'affixgroup' => $tierAffixGroup])
                            </h4>
                        @endif
                    </div>
                @endif
            </div>
            <div class="row no-gutters px-2 pb-2 pt-1 px-md-3 flex-fill d-flex description">
                <div class="col">
                    {{
                        empty($dungeonroute->description) ? __('views/common.dungeonroute.card.no_description') : $dungeonroute->description
                    }}
                </div>
            </div>
            <div class="row no-gutters p-2 enemy_forces">
                <div class="col">
                    @if( $enemyForcesWarning )
                        <span class="text-warning"> <i class="fas fa-exclamation-triangle"></i> </span>
                    @else
                        <span class="text-success"> <i class="fas fa-check-circle"></i> </span>
                    @endif
                    {{ sprintf(
                        '%s/%s (%s%%)',
                        $dungeonroute->enemy_forces,
                        $dungeonroute->dungeon->enemy_forces_required,
                        $enemyForcesPercentage
                        ) }}
                </div>
                <div class="col">
                    @if( $dungeonroute->level_min !== config('keystoneguru.levels.min') && $dungeonroute->level_max !== config('keystoneguru.levels.max'))
                        @include('common.dungeonroute.level', ['levelMin' => $dungeonroute->level_min, 'levelMax' => $dungeonroute->level_max])
                    @endif
                </div>
            </div>
            <div class="row no-gutters footer">
                <div class="col bg-card-footer px-2 py-1">
                    <small class="text-muted">
                        {{ __('views/common.dungeonroute.card.by_author') }}
                        @include('common.user.name', ['user' => $dungeonroute->author, 'link' => true, 'showAnonIcon' => false])
                        @if( $dungeonroute->avg_rating > 1 )
                            -
                            @include('common.dungeonroute.rating', ['count' => $dungeonroute->ratings->count(), 'rating' => (int) $dungeonroute->avg_rating])
                        @endif
                        -
                        <span data-toggle="tooltip" title="{{ $dungeonroute->updated_at->toDateTimeString('minute') }}">
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
                    <div class="dropdown-menu" aria-labelledby="route_menu_button_{{ $dungeonroute->public_key }}">
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
