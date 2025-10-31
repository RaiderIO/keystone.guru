@inject('cacheService', 'App\Service\Cache\CacheServiceInterface')
<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Service\Cache\CacheServiceInterface;

/**
 * @var CacheServiceInterface $cacheService
 * @var DungeonRoute          $dungeonroute
 * @var AffixGroup|null       $currentAffixGroup
 * @var AffixGroup|null       $tierAffixGroup
 * @var array                 $__env
 * @var boolean               $cache
 */

$showAffixes      ??= true;
$showDungeonImage ??= false;
$isAdmin           = Auth::user()?->hasRole(Role::ROLE_ADMIN) ?? false;
// Generate a unique string so we can assign affixes properly - route key is not unique enough since multiple cards can be on one page
$uniqueString     = uniqid();
?>

@section('scripts')
    @parent

    <script type="application/javascript">
        $(function () {
            // Set content right before it opens
            $('#dungeonroute_card_horizontal_{{ $uniqueString }} .affix_toggle').on('show.bs.popover', function () {
                // Wrap the rendered HTML in a container div and assign to data-content
                const html = '<div>' + handlebarsAffixGroupsParse({!!
                    $dungeonroute->affixes->each(function(AffixGroup $affixGroup) {
                        $affixGroup->setVisible([
                            'affixes'
                        ]);

                        $affixGroup->affixes->setVisible([
                            'name',
                            'image_name',
                        ]);
                    }) !!}, false) + '</div>';
                $(this).attr('data-content', html);
            });
        });
    </script>
@endsection

<?php
$cacheFn = static function ()

use ($uniqueString, $showAffixes, $showDungeonImage, $dungeonroute, $currentAffixGroup, $tierAffixGroup, $isAdmin, $__env)
{
    $seasonalAffix = $dungeonroute->getSeasonalAffix();

    if (!isset($tierAffixGroup)) {
        // Try to come up with a sensible default
        if ($dungeonroute->affixes->count() === 1) {
            $tierAffixGroup = $dungeonroute->affixes->first();
        } else {
            // If the affix list contains the current affix, we can use that to display the tier instead
            $tierAffixGroup = $currentAffixGroup === null ? null : ($dungeonroute->affixes->filter(static fn(AffixGroup $affixGroup) => $affixGroup->id === $currentAffixGroup->id)->isNotEmpty() ? $currentAffixGroup : null);
        }
    }
    // Attempt a default value if there's only one affix set
    $tierAffixGroup        = $tierAffixGroup ?? $dungeonroute->affixes->count() === 1 ?: null;
    $enemyForcesPercentage = $dungeonroute->getEnemyForcesPercentage();
    $enemyForcesWarning    = $dungeonroute->enemy_forces < $dungeonroute->mappingVersion->enemy_forces_required || $enemyForcesPercentage >= 105;
    $activeFloors          = $dungeonroute->dungeon->floorsForMapFacade($dungeonroute->mappingVersion, true)->get();
    $owlClass              = $dungeonroute->has_thumbnail && $activeFloors->count() > 1 ? 'multiple' : 'single';
    ob_start();
    ?>
<div id="dungeonroute_card_horizontal_{{ $uniqueString }}"
        class="row no-gutters m-xl-1 mx-0 my-3 card_dungeonroute horizontal {{ $showDungeonImage ? 'dungeon_image' : '' }}">
    <div class="col-xl-auto">
        <div class="{{ $owlClass }} light-slider-container">
            <ul class="light-slider {{ $owlClass }}">
                @if( $dungeonroute->has_thumbnail )
                    @foreach($dungeonroute->thumbnails as $thumbnail)
                        <li>
                            <img class="thumbnail"
                                 src="{{ $thumbnail->getURL() }}"
                                 style="display: {{ $loop->index === 0 ? 'block' : 'none' }}"
                                 alt="{{ __('view_common.dungeonroute.card.thumbnail_alt') }}"/>
                        </li>
                    @endforeach
                @else
                    <img class="dungeon" src="{{ $dungeonroute->dungeon->getImage32Url() }}"
                         alt="{{ __('view_common.dungeonroute.card.thumbnail_dungeon_alt') }}"/>
                @endif
            </ul>
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
                        <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}">
                            {{ $dungeonroute->title }}
                        </a>
                    </h4>
                </div>
                @if( !$dungeonroute->mappingVersion->isLatestForDungeon() )
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle text-warning"
                           title="{{ __('view_common.dungeonroute.card.outdated_mapping_version') }}"
                           data-toggle="tooltip"></i>
                    </div>
                @endif
            </div>
            <div class="row no-gutters px-2 pb-2 pt-1 px-md-3 flex-fill d-flex description_row">
                <div class="col d-flex d-xl-none">
                    @if(empty($dungeonroute->description))
                        &nbsp;
                    @else
                        {!! strip_tags($dungeonroute->description, config('keystoneguru.view.common.dungeonroute.card.allowed_tags')) !!}
                    @endif
                </div>
            </div>
            <div class="row no-gutters p-2 enemy_forces">
                <div class="col-auto">
                    @if( $enemyForcesWarning )
                        <span class="text-warning"> <i class="fas fa-exclamation-triangle"></i> </span>
                    @else
                        <span class="text-success"> <i class="fas fa-check-circle"></i> </span>
                    @endif
                    {{ sprintf(
                        '%s/%s (%s%%)',
                        $dungeonroute->enemy_forces,
                        $dungeonroute->mappingVersion->enemy_forces_required,
                        $enemyForcesPercentage
                        ) }}
                </div>
                <div class="col">
                    @if( $dungeonroute->level_min !== $dungeonroute->season?->key_level_min || $dungeonroute->level_max !== $dungeonroute->season?->key_level_max)
                        @include('common.dungeonroute.level', [
                            'season' => $dungeonroute->season,
                            'levelMin' => $dungeonroute->level_min,
                            'levelMax' => $dungeonroute->level_max,
                        ])
                    @endif
                </div>
            </div>
            <div class="row no-gutters footer">
                <div class="col bg-card-footer px-2 py-1">
                    <div class="row">
                        <div class="col">
                            <small class="text-muted">
                                    {{ __('view_common.dungeonroute.card.by_author') }}
                                    @include('common.user.name', ['user' => $dungeonroute->author, 'link' => true, 'showAnonIcon' => false])
                                    {{--                        @if( $dungeonroute->rating > 1 )--}}
                                    {{--                            ---}}
                                    {{--                            @include('common.dungeonroute.rating', ['count' => $dungeonroute->ratings->count(), 'rating' => (int) $dungeonroute->rating])--}}
                                    {{--                        @endif--}}
                                    -
                                <span data-toggle="tooltip" title="{{ $dungeonroute->updated_at->toDateTimeString('minute') }}">
                                    {{ sprintf(__('view_common.dungeonroute.card.updated_at'), $dungeonroute->updated_at->diffForHumans() ) }}
                                </span>
                            </small>
                        </div>

                        @if( $showAffixes )
                            <div class="col-auto pl-1 pr-0">
                                @if($seasonalAffix !== null)
                                    <div class="row no-gutters" data-container="body" data-toggle="popover" data-placement="bottom"
                                         data-html="true"
                                         data-content="" style="cursor: pointer;">
                                            <div class="col ml-1">
                                                <img class="select_icon"
                                                     src="{{ url($seasonalAffix->image_url) }}"
                                                     alt="{{ __($seasonalAffix->name) }}"/>
                                            </div>
                                    </div>
                                @endif
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
                            <i class="fas fa-flag"></i> {{ __('view_common.dungeonroute.card.report') }}
                        </a>
                        @if($isAdmin)
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item refresh_thumbnail"
                               data-publickey="{{ $dungeonroute->public_key }}">
                                <i class="fas fa-sync"></i> {{ __('view_common.dungeonroute.card.refresh_thumbnail') }}
                            </a>
                        @endif
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
$cache = true;

if ($cache) {
    $currentUserLocale = app()->getLocale();
// Echo the result of this function
    echo $cacheService->remember(
        DungeonRoute::getCardCacheKey($dungeonroute->id, 'horizontal', $currentUserLocale, $showAffixes, $showDungeonImage, $isAdmin),
        $cacheFn,
        config('keystoneguru.view.common.dungeonroute.card.cache.ttl')
    );
} else {
    echo $cacheFn();
}
?>
