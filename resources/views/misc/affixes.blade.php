@extends('layouts.sitepage', [
    'rootClass' => 'discover col-xl-8 offset-xl-2',
    'disableDefaultRootClasses' => true,
    'showLegalModal' => false,
    'title' => __('view_misc.affixes.title')
])
<?php
/**
 * @var $timewalkingEventService \App\Service\TimewalkingEvent\TimewalkingEventService
 * @var $seasonService \App\Service\Season\SeasonService
 * @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup
 * @var $nextAffixGroup \App\Models\AffixGroup\AffixGroup
 * @var $offset int
 * @var $expansion \App\Models\Expansion
 */

$region = \App\Models\GameServerRegion::getUserOrDefaultRegion();
$now    = \Carbon\Carbon::now();
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('content')
    @include('dungeonroute.discover.wallpaper', ['expansion' => $expansion])

    <div class="discover_panel">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-center">
                    {{ sprintf(__('view_misc.affixes.header'), __($region->name)) }}
                </h5>

                <table class="affixes_overview_table bg-secondary" width="100%">
                    <thead>
                    <tr>
                        <th width="20%">
                            {{ sprintf(__('view_misc.affixes.start_date'), $seasonService->getUserTimezone()) }}
                        </th>
                        <th width="20%">
                            {{ __('view_misc.affixes.2') }}
                        </th>
                        <th width="20%">
                            {{ __('view_misc.affixes.7') }}
                        </th>
                        <th width="20%">
                            {{ __('view_misc.affixes.14') }}
                        </th>
                        <th width="20%">
                            {{ __('view_misc.affixes.seasonal') }}
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $affixGroups     = $seasonService->getDisplayedAffixGroups($offset);
                    $affixGroupIndex = 0;
                    // @formatter:off
                    foreach($affixGroups as $index => $arr){
                        /** @var \Illuminate\Support\Carbon $startDate */
                        $startDate = $arr['date_start'];
                        /** @var \App\Models\AffixGroup\AffixGroup $affixGroup */
                        $affixGroup = $arr['affixgroup'];
                        $isCurrentWeek = $affixGroup->id === $currentAffixGroup->id && $startDate->diffInWeeks($now) <= 1;
                        $isFirst = $affixGroupIndex === 0;
                        $isLast = $affixGroups->count() - 1 === $affixGroupIndex;

                        $timewalkingEvent = $timewalkingEventService->getActiveTimewalkingEventAt($startDate);
                        ?>
                        @include('misc.table.affixrowtable', [
                            'timewalkingEvent' => null,
                            'affixGroup' => $affixGroup,
                            'isCurrentWeek' => $isCurrentWeek,
                            'isFirst' => $isFirst,
                            'isLast' => $isLast,
                            'startDate' => $startDate,
                            'showBottomBorder' => !($timewalkingEvent instanceof \App\Models\Timewalking\TimewalkingEvent),
                            'isOdd' => $affixGroupIndex % 2 == 0
                            ])
                        <?php
                        if ($timewalkingEvent !== null) {
                            $timewalkingEventAffixGroup = $timewalkingEventService->getAffixGroupAt($timewalkingEvent->expansion, $startDate);
                            if( $timewalkingEventAffixGroup !== null ) { ?>
                                @include('misc.table.affixrowtable', [
                                    'timewalkingEvent' => $timewalkingEvent,
                                    'affixGroup' => $timewalkingEventAffixGroup,
                                    'isCurrentWeek' => $isCurrentWeek,
                                    'isFirst' => $isFirst,
                                    'isLast' => $isLast,
                                    'showTopBorder' => false,
                                    'isOdd' => $affixGroupIndex % 2 == 0
                                    ])
                                <?php
                            }
                        }

                        ++$affixGroupIndex;
                    }

                    // @formatter:on

                    ?>
                    </tbody>
                </table>
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
                {{ sprintf(__('view_misc.affixes.updated_at'), '2023/Nov/14') }}
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
