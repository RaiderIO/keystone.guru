@inject('cacheService', 'App\Service\Cache\CacheServiceInterface')
<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;

/**
 * @var CacheServiceInterface $cacheService
 * @var DungeonRoute          $dungeonroute
 * @var array<string, mixed>  $__env
 * @var boolean               $cache
 */

$showDungeonImage ??= false;
$isAdmin          = Auth::check() && Auth::user()->hasRole(Role::ROLE_ADMIN);
// Generate a unique string so each card on the page has a stable, unique id
$uniqueString = uniqid();
?>
<?php
$cacheFn = static function ()

use (
    $uniqueString,
    $dungeonroute,
    $isAdmin,
    $__env
)

{
    $enemyForcesPercentage = $dungeonroute->getEnemyForcesPercentage();
    $enemyForcesWarning    = $dungeonroute->enemy_forces < $dungeonroute->mappingVersion->enemy_forces_required || $enemyForcesPercentage >= 105;
    // The map is demoted to a background texture: always a single image, never a carousel
    $backgroundUrl = $dungeonroute->has_thumbnail
        ? $dungeonroute->thumbnails->first()->getURL()
        : $dungeonroute->dungeon->getImageTransparentUrl();
    // favorites_count is only present when the route was loaded through the discover builders (withCount).
    // Weekly (Raider.IO) routes bypass those builders, so guard against a missing count.
    $favoritesCount = $dungeonroute->favorites_count ?? null;
    $ratingCount    = $dungeonroute->rating_count;
    ob_start();
    ?>
<div id="dungeonroute_card_poster_{{ $uniqueString }}"
     class="card_dungeonroute poster m-xl-1 mx-0 my-3"
     style="background-image: url('{{ $backgroundUrl }}');">
    <div class="d-flex flex-column h-100 poster_scrim">
        <div class="row g-0 p-2 poster_top">
            <div class="col">
                @if( !$dungeonroute->mappingVersion->isLatestForDungeon() )
                    <i class="fas fa-exclamation-triangle text-warning"
                       title="{{ __('view_common.dungeonroute.card.outdated_mapping_version') }}"
                       data-bs-toggle="tooltip"></i>
                @endif
            </div>
            <div class="col-auto">
                <button id="route_menu_button_{{ $dungeonroute->public_key }}"
                        class="btn btn-sm menu_actions_btn py-0"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end"
                     aria-labelledby="route_menu_button_{{ $dungeonroute->public_key }}">
                    <a class="dropdown-item" href="#" data-bs-toggle="modal"
                       data-bs-target="#userreport_dungeonroute_modal"
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

        <div class="flex-fill"></div>

        <div class="row g-0 px-2 align-items-end poster_title_row">
            <div class="col">
                <h4 class="mb-0 title">
                    <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}">
                        {{ $dungeonroute->title }}
                    </a>
                </h4>
            </div>
            <div class="col-auto ps-2 poster_enemy_forces">
                @if( $enemyForcesWarning )
                    <span class="text-warning"><i class="fas fa-exclamation-triangle"></i></span>
                @else
                    <span class="text-success"><i class="fas fa-check-circle"></i></span>
                @endif
                {{ sprintf('%s%%', $enemyForcesPercentage) }}
            </div>
        </div>

        @if( $dungeonroute->level_min !== $dungeonroute->season?->key_level_min || $dungeonroute->level_max !== $dungeonroute->season?->key_level_max)
            <div class="row g-0 px-2 pt-1 poster_level">
                <div class="col">
                    @include('common.dungeonroute.level', [
                        'season' => $dungeonroute->season,
                        'levelMin' => $dungeonroute->level_min,
                        'levelMax' => $dungeonroute->level_max,
                        'minAnchorKeyLevelWidth' => 2,
                    ])
                </div>
            </div>
        @endif

        <div class="row g-0 bg-card-footer px-2 py-1 poster_footer">
            <div class="col">
                <div class="poster_author d-flex align-items-center">
                    @include('common.user.name', ['user' => $dungeonroute->author, 'link' => true, 'showAnonIcon' => false])
                    {{-- Reserved slot for the future Raider.IO author trust badge (see #3349) --}}
                    <span class="poster_trust_badge ms-1"></span>
                </div>
                <div class="poster_social small text-muted">
                    @if( $ratingCount > 0 )
                        <span class="poster_rating">
                            @include('common.dungeonroute.rating', ['count' => $ratingCount, 'rating' => (int) round($dungeonroute->rating)])
                        </span>
                    @endif
                    <span class="poster_views ms-1" data-bs-toggle="tooltip"
                          title="{{ sprintf(__('view_common.dungeonroute.poster.views'), $dungeonroute->views) }}">
                        <i class="fas fa-eye"></i> {{ $dungeonroute->views }}
                    </span>
                    @if( $favoritesCount !== null )
                        <span class="poster_favorites ms-1" data-bs-toggle="tooltip"
                              title="{{ sprintf(__('view_common.dungeonroute.poster.favorites'), $favoritesCount) }}">
                            <i class="fas fa-heart"></i> {{ $favoritesCount }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

    <?php
    return ob_get_clean();
};

if ($cache) {
    /** @var User|null $authUser */
    $authUser          = Auth::user();
    $currentUserLocale = Auth::check() ? $authUser->locale : 'en_US';
// Echo the result of this function
    echo $cacheService->remember(
        DungeonRoute::getCardCacheKey($dungeonroute->id, 'poster', $currentUserLocale, 0, (int)$showDungeonImage, (int)$isAdmin),
        $cacheFn,
        config('keystoneguru.view.common.dungeonroute.card.cache.ttl')
    );
} else {
    echo $cacheFn();
}
?>
