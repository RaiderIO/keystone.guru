@inject('cacheService', 'App\Service\Cache\CacheServiceInterface')
<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Support\Carbon;

/**
 * @var CacheServiceInterface $cacheService
 * @var DungeonRoute          $dungeonroute
 * @var int                   $rank
 * @var array<string, mixed>  $__env
 * @var boolean               $cache
 */

$isAdmin = Auth::check() && Auth::user()->hasRole(Role::ROLE_ADMIN);
?>
<?php
// The route-dependent HTML is cached per route; the rank is positional and therefore rendered outside the cache.
$cacheFn = static function ()

use (
    $dungeonroute,
    $isAdmin,
    $__env
)

{
    $enemyForcesPercentage = $dungeonroute->getEnemyForcesPercentage();
    $enemyForcesWarning    = $dungeonroute->enemy_forces < $dungeonroute->mappingVersion->enemy_forces_required || $enemyForcesPercentage >= 105;
    $backgroundUrl = $dungeonroute->has_thumbnail
        ? $dungeonroute->thumbnails->first()->getURL()
        : $dungeonroute->dungeon->getImageTransparentUrl();
    $ratingCount = $dungeonroute->rating_count;
    // The key-level chip is only meaningful when the route deviates from the season's catch-all range
    $showLevel = $dungeonroute->level_min !== $dungeonroute->season?->key_level_min
        || $dungeonroute->level_max !== $dungeonroute->season?->key_level_max;
    // A route counts as "new" while it is within its first two weeks of being published
    $isNew = $dungeonroute->published_at->greaterThan(Carbon::now()->subDays(14));
    ob_start();
    ?>
<div class="d-flex align-items-center flex-fill flex-wrap leaderboard_row_inner">
    <div class="leaderboard_thumbnail" style="background-image: url('{{ $backgroundUrl }}');"></div>
    <div class="leaderboard_main">
        <div class="leaderboard_title">
            <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}">
                {{ $dungeonroute->title }}
            </a>
            @if( !$dungeonroute->mappingVersion->isLatestForDungeon() )
                <i class="fas fa-exclamation-triangle text-warning ms-1"
                   title="{{ __('view_common.dungeonroute.card.outdated_mapping_version') }}"
                   data-bs-toggle="tooltip"></i>
            @endif
            @if( $isNew )
                <span class="badge bg-success ms-1 leaderboard_new">{{ __('view_common.dungeonroute.cardrow.new') }}</span>
            @endif
        </div>
        <div class="leaderboard_author text-muted small">
            @include('common.user.name', ['user' => $dungeonroute->author, 'link' => true, 'showAnonIcon' => false])
        </div>
    </div>
    <div class="leaderboard_stats d-flex align-items-center text-muted small ms-auto">
        @if( $showLevel )
            <span class="leaderboard_level_chip me-3">
                {{ $dungeonroute->level_min === $dungeonroute->level_max
                    ? sprintf('+%d', $dungeonroute->level_min)
                    : sprintf('+%d – +%d', $dungeonroute->level_min, $dungeonroute->level_max) }}
            </span>
        @endif
        @if( $enemyForcesWarning )
            <span class="leaderboard_enemy_forces text-warning me-3">
                <i class="fas fa-exclamation-triangle"></i> {{ sprintf('%s%%', $enemyForcesPercentage) }}
            </span>
        @endif
        @if( $ratingCount > 0 )
            <span class="leaderboard_rating me-3">
                @include('common.dungeonroute.rating', ['count' => $ratingCount, 'rating' => (int) round($dungeonroute->rating)])
            </span>
        @endif
        <span class="leaderboard_views me-3" data-bs-toggle="tooltip"
              title="{{ sprintf(__('view_common.dungeonroute.cardrow.views'), $dungeonroute->views) }}">
            <i class="fas fa-eye"></i> {{ abbreviateNumber($dungeonroute->views) }}
        </span>
    </div>
    <div class="leaderboard_actions">
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
    <?php
    return ob_get_clean();
};
?>
<div class="card_dungeonroute leaderboard_row d-flex align-items-center">
    <div class="leaderboard_rank text-secondary text-end">{{ $rank }}</div>
    <?php
    if ($cache) {
        /** @var User|null $authUser */
        $authUser          = Auth::user();
        $currentUserLocale = Auth::check() ? $authUser->locale : 'en_US';
        echo $cacheService->remember(
            DungeonRoute::getCardCacheKey($dungeonroute->id, 'row', $currentUserLocale, 0, 0, (int)$isAdmin),
            $cacheFn,
            config('keystoneguru.view.common.dungeonroute.card.cache.ttl')
        );
    } else {
        echo $cacheFn();
    }
    ?>
</div>
