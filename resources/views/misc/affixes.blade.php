@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'showLegalModal' => false,
    'title' => __('view_misc.affixes.title'),
])
<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Service\Season\SeasonService;
use App\Service\TimewalkingEvent\TimewalkingEventService;
use Illuminate\Support\Collection;

/**
 * @var TimewalkingEventService $timewalkingEventService
 * @var SeasonService           $seasonService
 * @var AffixGroup              $currentAffixGroup
 * @var AffixGroup              $nextAffixGroup
 * @var int                     $offset
 * @var Expansion               $expansion
 * @var GameServerRegion        $userOrDefaultRegion
 */

$affixGroupsBySeason = collect();

foreach ($seasonService->getDisplayedAffixGroups($offset) as $affixGroupArr) {
    $affixGroup = $affixGroupArr['affix_group'];

    if (!$affixGroupsBySeason->has($affixGroup->season_id)) {
        $affixGroupsBySeason->put($affixGroup->season_id, collect());
    }

    /** @var Collection<AffixGroup> $currentSeasonAffixGroups */
    $currentSeasonAffixGroups = $affixGroupsBySeason->get($affixGroup->season_id);
    $currentSeasonAffixGroups->push($affixGroupArr);
}
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['expansion' => $expansion])

    <div class="discover_panel">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-center">
                    {{ sprintf(__('view_misc.affixes.header'), __($userOrDefaultRegion->name)) }}
                </h5>
                @foreach ($affixGroupsBySeason as $seasonId => $affixGroups)
                    @include('misc.table.affixtable', [
                        'timewalkingEventService' => $timewalkingEventService,
                        'seasonService' => $seasonService,
                        'currentAffixGroup' => $currentAffixGroup,
                        'nextAffixGroup' => $nextAffixGroup,
                        'offset' => $offset,
                        'expansion' => $expansion,
                        'affixGroups' => $affixGroups,
                        'isNewSeason' => $loop->index !== 0
                    ])
                @endforeach
                <div class="row mt-2">
                    <div class="col">

                    </div>
                    <div class="col-auto">
                        <ul class="pagination" role="navigation">
                            <li class="page-item">
                                <a class="page-link" href="{{ route('misc.affixes', ['offset' => $offset - 1]) }}">
                                    ‹ {{ __('view_misc.affixes.previous') }}
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="{{ route('misc.affixes', ['offset' => $offset + 1]) }}">
                                    {{ __('view_misc.affixes.next') }} ›
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-4 text-center">
            <p>
                {{ sprintf(__('view_misc.affixes.updated_at'), '2024/Oct/23') }}
                <a href="https://mythicpl.us/" target="_blank" rel="noopener noreferrer">
                    https://mythicpl.us/ <i class="fas fa-external-link-alt"></i>
                </a>
            </p>
        </div>
    </div>

    <div class="discover">
        @if( !$adFree && !$isMobile)
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['id' => 'site_middle_affixes', 'type' => 'header', 'reportAdPosition' => 'top-right'])
            </div>
        @endif

        @include('dungeonroute.discover.panel', [
            'expansion' => $expansion,
            'title' => __('view_misc.affixes.popular_routes_by_current_affixes'),
            'link' => route('dungeonroutes.thisweek', ['expansion' => $expansion]),
            'currentAffixGroup' => $currentAffixGroup,
            'affixgroup' => $currentAffixGroup,
            'dungeonroutes' => $dungeonroutes['thisweek'],
            'showMore' => $dungeonroutes['thisweek']->count() >= config('keystoneguru.discover.limits.affix_overview'),
            'showDungeonImage' => true,
        ])

        @if( !$adFree && !$isMobile)
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['id' => 'site_middle_affixes', 'type' => 'header', 'reportAdPosition' => 'top-right'])
            </div>
        @endif

        <?php /* The next week's affix group is current for that week */ ?>
        @include('dungeonroute.discover.panel', [
            'expansion' => $expansion,
            'title' => __('view_misc.affixes.popular_routes_by_next_affixes'),
            'link' => route('dungeonroutes.nextweek', ['expansion' => $expansion]),
            'currentAffixGroup' => $nextAffixGroup,
            'affixgroup' => $nextAffixGroup,
            'dungeonroutes' => $dungeonroutes['nextweek'],
            'showMore' => $dungeonroutes['nextweek']->count() >= config('keystoneguru.discover.limits.affix_overview'),
            'showDungeonImage' => true,
        ])

        @if( !$adFree && !$isMobile)
            <div align="center" class="mt-4">
                @include('common.thirdparty.adunit', ['id' => 'site_middle_affixes', 'type' => 'header', 'reportAdPosition' => 'top-right'])
            </div>
        @endif
    </div>

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
