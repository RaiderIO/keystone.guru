@inject('cacheService', 'App\Service\Cache\CacheServiceInterface')

<?php
/** @var $cacheService \App\Service\Cache\CacheService */
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $tierAffixGroup \App\Models\AffixGroup|null */
/** @var $__env array */

// Echo the result of this function
echo $cacheService->remember(sprintf('view:dungeonroute_card_%s', $dungeonroute->id), function() use ($dungeonroute, $tierAffixGroup, $__env) {
// Attempt a default value if there's only one affix set
$tierAffixGroup = $tierAffixGroup ?? $dungeonroute->affixes->count() === 1 ? $dungeonroute->affixes->first() : null;
$showAffixes = $showAffixes ?? true;
$showDungeonImage = $showDungeonImage ?? false;
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
                             src="{{ url(sprintf('/images/route_thumbnails/%s_%s.png', $dungeonroute->public_key, $loop->index + 1)) }}"/>
                    @endforeach
                @else
                    <img class="dungeon" src="{{ url(sprintf(
                            '/images/dungeons/%s/%s_3-2.jpg',
                            $dungeonroute->dungeon->expansion->shortname,
                            $dungeonroute->dungeon->key
                            )) }}"/>
                @endif
            </div>
        </div>
    </div>
    <div class="col">
        <div class="d-flex flex-column h-100 bg-card"
             @if($showDungeonImage)
             style="background-image: url('{{
                    url(sprintf('/images/dungeons/%s/%s_transparent.png', $dungeonroute->dungeon->expansion->shortname, $dungeonroute->dungeon->key))
                    }}'); background-size: cover;"
            @endif
        >
            <div class="row no-gutters p-2 header">
                <div class="col">
                    <h4 class="mb-0">
                        <a href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute->public_key]) }}">
                            {{ $dungeonroute->title }}
                        </a>
                    </h4>
                </div>
                @if( $showAffixes )
                    <div class="col-auto">
                        <?php
                        $isTyrannical = $dungeonroute->isTyrannical();
                        $isFortified = $dungeonroute->isFortified();
                        ob_start();
                        ?>
                        @foreach($dungeonroute->affixes as $affixgroup)
                            <div class="row no-gutters">
                                @include('common.affixgroup.affixgroup', ['affixgroup' => $affixgroup, 'showText' => false, 'dungeon' => $dungeonroute->dungeon])
                            </div>
                        @endforeach
                        <?php $affixes = ob_get_clean(); ?>
                        @if($isTyrannical && $isFortified)
                            <div data-container="body" data-toggle="popover" data-placement="bottom"
                                 data-html="true"
                                 data-content="{{ $affixes }}" style="cursor: pointer;">
                                <img class="select_icon"
                                     src="{{ url('/images/affixes/keystone.jpg') }}"/>
                            </div>
                        @elseif($isTyrannical)
                            <div data-container="body" data-toggle="popover" data-placement="bottom"
                                 data-html="true"
                                 data-content="{{ $affixes }}" style="cursor: pointer;">
                                <img class="select_icon"
                                     src="{{ url('/images/affixes/tyrannical.jpg') }}"/>
                            </div>
                        @elseif($isFortified)
                            <div data-container="body" data-toggle="popover" data-placement="bottom"
                                 data-html="true"
                                 data-content="{{ $affixes }}" style="cursor: pointer;">
                                <img class="select_icon"
                                     src="{{ url('/images/affixes/fortified.jpg') }}"/>
                            </div>
                        @endif
                    </div>
                    <div class="col-auto px-1">
                        @if($tierAffixGroup !== null)
                            <h4 class="font-weight-bold px-1">
                                @include('common.dungeonroute.tier', ['dungeon' => $dungeonroute->dungeon, 'affixgroup' => $tierAffixGroup])
                            </h4>
                        @endif
                    </div>
                @endif
                @auth
                    @if(Auth::user()->hasRole('admin'))
                        <div class="col-auto px-1 refresh_thumbnail" title="{{ __('Refresh thumbnail') }}"
                             data-toggle="tooltip" data-publickey="{{ $dungeonroute->public_key }}">
                            <a class="btn p-0">
                                <span class="refresh">
                                    <i class="fas fa-sync"></i>
                                </span>
                                <span class="loader" style="display: none;">
                                    <i class="fas fa-circle-notch fa-spin"></i>
                                </span>
                            </a>
                        </div>
                    @endif
                @endauth
            </div>
            <div class="row no-gutters px-2 pb-2 pt-1 px-md-3 flex-fill d-flex description">
                <div class="col">
                    {{
                        empty($dungeonroute->description) ? __('No description') : $dungeonroute->description
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
                        __('%s/%s (%s%%)'),
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
                        {{ __('By') }}
                        <a href="{{ route('profile.view', ['user' => $dungeonroute->author->id]) }}">
                            {{ $dungeonroute->author->name }}
                        </a>
                        @if( $dungeonroute->avg_rating > 1 )
                            -
                            @include('common.dungeonroute.rating', ['count' => $dungeonroute->ratings->count(), 'rating' => (int) $dungeonroute->avg_rating])
                        @endif
                        -
                        {{ sprintf(__('Last updated %s'), $dungeonroute->updated_at->diffForHumans() ) }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
return ob_get_clean();
});
?>