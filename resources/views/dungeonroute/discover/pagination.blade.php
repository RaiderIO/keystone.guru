<?php
/**
 * Classic Previous/Next pagination for the reworked discover leaderboard pages. The links reuse the
 * current URL and only swap the `page` query parameter, keeping them crawlable for SEO.
 *
 * @var int  $page
 * @var bool $hasMore
 */
$page    ??= 1;
$hasMore ??= false;
?>
@if($page > 1 || $hasMore)
    <nav class="row mt-4 discover_pagination" aria-label="{{ __('view_dungeonroute.discover.pagination.label') }}">
        <div class="col d-flex justify-content-between align-items-center">
            @if($page > 1)
                <a class="btn btn-outline-secondary" rel="prev"
                   href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}">
                    <i class="fas fa-chevron-left me-1"></i>{{ __('view_dungeonroute.discover.pagination.previous') }}
                </a>
            @else
                <span></span>
            @endif

            @if($hasMore)
                <a class="btn btn-outline-secondary ms-auto" rel="next"
                   href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}">
                    {{ __('view_dungeonroute.discover.pagination.next') }}<i class="fas fa-chevron-right ms-1"></i>
                </a>
            @endif
        </div>
    </nav>
@endif
