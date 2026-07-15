@inject('cacheService', 'App\Service\Cache\CacheServiceInterface')
<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;

/**
 * @var CacheServiceInterface $cacheService
 * @var DungeonRoute          $dungeonroute
 * @var string|null           $archetype
 * @var int|null              $heroRank
 * @var array<string, mixed>  $__env
 * @var boolean               $cache
 */

$archetype ??= null;
// When a route is promoted into the hero band without a Raider.IO archetype, its popularity rank
// (1-based) labels the card instead ("#1 community route"); null falls back to the generic label.
$heroRank ??= null;
$isAdmin   = Auth::check() && Auth::user()->hasRole(Role::ROLE_ADMIN);
// Generate a unique string so each card on the page has a stable, unique id
$uniqueString = uniqid();
?>
<?php
$cacheFn = static function ()

use (
    $uniqueString,
    $dungeonroute,
    $archetype,
    $heroRank,
    $isAdmin,
    $__env
)

{
    $enemyForcesPercentage = $dungeonroute->getEnemyForcesPercentage();
    $enemyForcesWarning    = $dungeonroute->enemy_forces < $dungeonroute->mappingVersion->enemy_forces_required || $enemyForcesPercentage >= 105;
    // The map is demoted to a cinematic background: prefer the dedicated hero-sized render
    $backgroundUrl = $dungeonroute->getHeroThumbnailUrl() ?? $dungeonroute->dungeon->getImageTransparentUrl();
    $ratingCount = $dungeonroute->rating_count;
    // The favorites count is only present when the route was loaded with withCount('favorites'); never trigger a query
    $favoritesCount = $dungeonroute->favorites_count ?? null;
    // Enemy forces per pull, ordered by pull index - drives the "route fingerprint" bar graph
    $pullForces = $dungeonroute->getEnemyForcesPerKillZone();
    // The key-level chip is only meaningful when the route deviates from the season's catch-all range
    $showLevel = $dungeonroute->level_min !== $dungeonroute->season?->key_level_min
        || $dungeonroute->level_max !== $dungeonroute->season?->key_level_max;
    // An archetype maps onto a Raider.IO weekly-route tag; a null archetype is a promoted community
    // route, labelled by its popularity rank ("#1 community route") when one is provided.
    $eyebrow = $archetype === null
        ? ($heroRank !== null
            ? sprintf(__('view_common.dungeonroute.cardhero.ranked_community_route'), $heroRank)
            : __('view_common.dungeonroute.cardhero.top_community_route'))
        : __(sprintf('view_dungeonroute.discover.dungeon.overview.archetypes.%s.label', $archetype));
    $description = $archetype === null
        ? null
        : __(sprintf('view_dungeonroute.discover.dungeon.overview.archetypes.%s.description', $archetype));
    ob_start();
    ?>
<div id="dungeonroute_card_hero_{{ $uniqueString }}"
     class="card_dungeonroute hero"
     style="background-image: url('{{ $backgroundUrl }}');">
    <div class="d-flex flex-column h-100 hero_scrim">
        <div class="row g-0 p-3 hero_top align-items-start">
            <div class="col">
                <div class="hero_eyebrow text-uppercase">
                    {{ $eyebrow }}
                </div>
            </div>
            <div class="col-auto ps-2 d-flex align-items-center">
                @if( !$dungeonroute->mappingVersion->isLatestForDungeon() )
                    <i class="fas fa-exclamation-triangle text-warning me-2"
                       title="{{ __('view_common.dungeonroute.card.outdated_mapping_version') }}"
                       data-bs-toggle="tooltip"></i>
                @endif
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

        <div class="row g-0 px-3 hero_title_row">
            <div class="col">
                <h3 class="mb-1 hero_title">
                    <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}">
                        {{ $dungeonroute->title }}
                    </a>
                </h3>
            </div>
        </div>

        @if( $description !== null )
            <div class="row g-0 px-3 hero_description">
                <div class="col">
                    {{ $description }}
                </div>
            </div>
        @endif

        <div class="row g-0 px-3 pt-2 hero_author">
            <div class="col d-flex align-items-center">
                @include('common.user.name', ['user' => $dungeonroute->author, 'link' => true, 'showAnonIcon' => false])
            </div>
        </div>

        <?php // Stats are ordered rarest -> always-present so the ever-present pull graph and views anchor the right edge ?>
        <div class="row g-0 px-3 py-3 hero_stats">
            <div class="col d-flex align-items-center flex-wrap">
                @if( $enemyForcesWarning )
                    <span class="hero_enemy_forces text-warning me-3">
                        <i class="fas fa-exclamation-triangle"></i> {{ sprintf('%s%%', $enemyForcesPercentage) }}
                    </span>
                @endif
                @if( $ratingCount > 0 )
                    <span class="hero_rating me-3">
                        @include('common.dungeonroute.rating', ['count' => $ratingCount, 'rating' => (int) round($dungeonroute->rating)])
                    </span>
                @endif
                @if( $favoritesCount > 0 )
                    <span class="hero_favorites me-3" data-bs-toggle="tooltip"
                          title="{{ sprintf(__('view_common.dungeonroute.cardhero.favorites'), $favoritesCount) }}">
                        <i class="fas fa-heart"></i> {{ abbreviateNumber($favoritesCount) }}
                    </span>
                @endif
                @if( $showLevel )
                    <span class="hero_level_chip me-3">
                        {{ $dungeonroute->level_min === $dungeonroute->level_max
                            ? sprintf('+%d', $dungeonroute->level_min)
                            : sprintf('+%d – +%d', $dungeonroute->level_min, $dungeonroute->level_max) }}
                    </span>
                @endif
                @include('common.dungeonroute.pullgraph', [
                    'pullForces'  => $pullForces,
                    'chartHeight' => 26,
                    'fill'        => 'rgba(255, 255, 255, 0.6)',
                    'bossFill'    => 'rgba(240, 180, 60, 0.95)',
                    'graphClass'  => 'hero_pull_graph me-3',
                    'tooltipKey'  => 'view_common.dungeonroute.cardhero.pulls',
                ])
                <span class="hero_views" data-bs-toggle="tooltip"
                      title="{{ sprintf(__('view_common.dungeonroute.cardhero.views'), $dungeonroute->views) }}">
                    <i class="fas fa-eye"></i> {{ abbreviateNumber($dungeonroute->views) }}
                </span>
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
// Echo the result of this function - the archetype (or hero rank) is folded into the orientation so no two hero variants share a cache entry
    echo $cacheService->remember(
        DungeonRoute::getCardCacheKey($dungeonroute->id, sprintf('hero_%s', $archetype ?? sprintf('top%s', $heroRank ?? '')), $currentUserLocale, 0, 0, (int)$isAdmin),
        $cacheFn,
        config('keystoneguru.view.common.dungeonroute.card.cache.ttl')
    );
} else {
    echo $cacheFn();
}
?>
