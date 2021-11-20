@extends('layouts.sitepage', ['rootClass' => 'discover col-xl-10 offset-xl-1', 'showLegalModal' => false, 'title' => __('views/misc.affixes.title')])
<?php
/**
 * @var $seasonService \App\Service\Season\SeasonService
 * @var $currentAffixGroup \App\Models\AffixGroup
 * @var $nextAffixGroup \App\Models\AffixGroup
 * @var $offset int
 * @var $expansion \App\Models\Expansion
 */

$region = \App\Models\GameServerRegion::getUserOrDefaultRegion();
?>
@include('common.general.inline', ['path' => 'dungeonroute/discover/discover'])

@section('content')
    <div class="discover_panel">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-center">
                    {{ sprintf(__('views/misc.affixes.header'), __($region->name)) }}
                </h5>

                <table class="affixes_overview_table table-striped bg-secondary" width="100%">
                    <thead>
                    <tr>
                        <th width="20%">
                            {{ sprintf(__('views/misc.affixes.start_date'), $seasonService->getUserTimezone()) }}
                        </th>
                        <th width="20%">
                            {{ __('views/misc.affixes.2') }}
                        </th>
                        <th width="20%">
                            {{ __('views/misc.affixes.4') }}
                        </th>
                        <th width="20%">
                            {{ __('views/misc.affixes.7') }}
                        </th>
                        <th width="20%">
                            {{ __('views/misc.affixes.10_seasonal') }}
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    // Whatever group we're highlighting
                    $currentAffixGroup = $seasonService->getCurrentSeason()->getCurrentAffixGroup();

                    $affixGroups = $seasonService->getDisplayedAffixGroups($offset);
                    $affixGroupIndex = 0;
                    foreach($affixGroups as $index => $arr){
                    /** @var \Illuminate\Support\Carbon $startDate */
                    $startDate = $arr['date_start'];
                    /** @var \App\Models\AffixGroup $affixGroup */
                    $affixGroup = $arr['affixgroup'];
                    ?>
                    <tr class="table_row">
                        <?php
                        // Current week if we found the current affix group for this region
                        $currentWeekClass = $affixGroup->id === $currentAffixGroup->id && $startDate->diffInWeeks(\Carbon\Carbon::now()) <= 1 ? 'current_week ' : '';
                        ?>
                        <td>
                            <div class="affix_row first_column {{ $currentWeekClass }}">
                    <span>
                        {{ $startDate->format('Y/M/d') }}
                    </span>
                                <span class="d-xl-inline d-none">
                        {{ $startDate->format(' @ H\h') }}
                    </span>
                            </div>
                        </td>
                        <?php
                        $affixIndex = 0;
                        foreach($affixGroup->affixes as $affix) {
                        $lastColumn = count($affixGroup->affixes) - 1 === $affixIndex;
                        $class = $currentWeekClass;
                        $class .= $lastColumn ? 'last_column ' : '';
                        $class .= ($affixGroupIndex === 0) ? 'first_row ' : '';
                        $class .= $affixGroups->count() - 1 === $affixGroupIndex ? 'last_row ' : '';
                        ?>
                        <td>
                            <div class="affix_row {{ $class }}">
                                <div class="row no-gutters">
                                    <div
                                        class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->key) }}"
                                        data-toggle="tooltip"
                                        title="{{ __($affix->description) }}"
                                        style="height: 24px;">
                                    </div>
                                    <div class="col d-lg-block d-none pl-1">
                                        @if($lastColumn && $affixGroup->seasonal_index !== null)
                                            {{ sprintf(__('affixes.seasonal_index_preset'), __($affix->name), $affixGroup->seasonal_index + 1) }}
                                        @else
                                            {{ __($affix->name) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td><?php
                        $affixIndex++;
                        }
                        $affixGroupIndex++
                        ?>
                    </tr><?php
                    } ?>
                    </tbody>
                </table>

                <div class="row mt-2">
                    <div class="col">

                    </div>
                    <div class="col-auto">
                        <ul class="pagination" role="navigation">
                            <li class="page-item">
                                <a class="page-link" href="{{ route('misc.affixes', ['offset' => $offset - 1]) }}">
                                    ‹ {{ __('views/misc.affixes.previous') }}
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="{{ route('misc.affixes', ['offset' => $offset + 1]) }}">
                                    {{ __('views/misc.affixes.next') }} ›
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-4 text-center">
            <p>
                {{ sprintf(__('views/misc.affixes.updated_at'), '2021/Sep/15') }}
                <a href="https://mythicpl.us/" target="_blank" rel="noopener noreferrer">
                    https://mythicpl.us/ <i class="fas fa-external-link-alt"></i>
                </a>
            </p>
        </div>
    </div>

    <div class="discover">
        @include('dungeonroute.discover.panel', [
            'title' => __('views/misc.affixes.popular_routes_by_current_affixes'),
            'link' => route('dungeonroutes.thisweek', ['expansion' => $expansion]),
            'currentAffixGroup' => $currentAffixGroup,
            'affixgroup' => $currentAffixGroup,
            'dungeonroutes' => $dungeonroutes['thisweek'],
            'showMore' => true,
            'showDungeonImage' => true,
        ])


        @include('dungeonroute.discover.panel', [
            'title' => __('views/misc.affixes.popular_routes_by_next_affixes'),
            'link' => route('dungeonroutes.nextweek', ['expansion' => $expansion]),
            // The next week's affix group is current for that week
            'currentAffixGroup' => $nextAffixGroup,
            'affixgroup' => $nextAffixGroup,
            'dungeonroutes' => $dungeonroutes['nextweek'],
            'showMore' => true,
            'showDungeonImage' => true,
        ])
    </div>

    @component('common.general.modal', ['id' => 'userreport_dungeonroute_modal'])
        @include('common.modal.userreport.dungeonroute')
    @endcomponent
@endsection
