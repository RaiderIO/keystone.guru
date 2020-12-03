@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Affixes')])
<?php
/** @var \App\Service\Season\SeasonService $seasonService */
/** @var $offset */

$region = \App\Models\GameServerRegion::getUserOrDefaultRegion();
$timezone = null;
if (Auth::check()) {
    $timezone = Auth::user()->timezone;
}
if ($timezone === null) {
    $timezone = config('app.timezone');
}
?>
@section('header-title', sprintf(__('Weekly affixes in %s'), $region->name))

@section('content')
    <div class="container">
        @if(!isAlertDismissed('shadowlands-affixes-alert'))
            <div class="alert alert-warning alert-dismissible">
                <a href="#" class="close" data-dismiss="alert" aria-label="close" data-alert-dismiss-id="shadowlands-affixes-alert">
                    <i class="fas fa-times"></i>
                </a>
                {{ __('The Shadowlands affixes are preliminary and not final. As soon as an affix schedule is known, this page will be updated. Please stay tuned.') }}
            </div>
        @endif
    </div>
    <table class="affixes_overview_table table-striped" width="100%">
        <thead>
        <tr>
            <th width="20%">
                {{ __('Start date') . sprintf(' (%s)', $timezone) }}
            </th>
            <th width="20%">
                {{ __('+2') }}
            </th>
            <th width="20%">
                {{ __('+4') }}
            </th>
            <th width="20%">
                {{ __('+7') }}
            </th>
            <th width="20%">
                {{ __('+10 (Seasonal)') }}
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
                        <div class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->name) }}"
                             data-toggle="tooltip"
                             title="{{ $affix->description }}"
                             style="height: 24px;">
                        </div>
                        <div class="col d-lg-block d-none pl-1">
                            {{ $affix->name }}
                            @if($lastColumn)
                                @if($affixGroup->season->presets > 0 )
                                    {{ __(sprintf('preset %s', $affixGroup->season->getPresetAt($startDate))) }}
                                @endif
                                @isset($affixGroup->seasonal_index)
                                    {{ __(sprintf('(%s)', $affixGroup->getSeasonalIndexAsLetter())) }}
                                @endisset
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

    <ul class="pagination" role="navigation">
        <li class="page-item">
            <a class="page-link" href="{{ route('misc.affixes', ['offset' => $offset - 1]) }}">
                ‹ {{ __('Previous') }}
            </a>
        </li>
        <li class="page-item">
            <a class="page-link" href="{{ route('misc.affixes', ['offset' => $offset + 1]) }}">
                {{ __('Next') }} ›
            </a>
        </li>
    </ul>

    <div class="mt-4 col-12 text-center">
        <p>
            {{ __('Last updated at 2020/Dec/04.') }}
            {{ __('For more information about affixes and M+, please visit') }}
            <a href="https://mythicpl.us/" target="_blank">https://mythicpl.us/ <i class="fas fa-external-link-alt"></i></a>
        </p>
    </div>
@endsection