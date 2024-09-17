<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use App\Service\TimewalkingEvent\TimewalkingEventService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @var TimewalkingEventService                                        $timewalkingEventService
 * @var SeasonServiceInterface                                         $seasonService
 * @var AffixGroup                                                     $currentAffixGroup
 * @var AffixGroup                                                     $nextAffixGroup
 * @var int                                                            $offset
 * @var Expansion                                                      $expansion
 * @var Collection<array{date_start: Carbon, affix_group: AffixGroup}> $affixGroups
 * @var bool                                                           $isNewSeason
 */
// Juuusstt to be sure
if ($affixGroups->isEmpty()) {
    return;
}

$now = Carbon::now();
?>

<table class="affixes_overview_table bg-secondary mb-2" width="100%">
    <thead>
    <?php
    /** @var array{date_start: Carbon, affix_group: AffixGroup} $firstAffixGroupArr */
    $firstAffixGroupArr = $affixGroups->first();
    $season             = Season::findOrFail($firstAffixGroupArr['affix_group']->season_id);
    $firstAffixGroup    = $firstAffixGroupArr['affix_group'];
    $firstAffixGroup->load('affixGroupCouplings');
    ?>
    <tr class="table_row text-center bg-dark">
        <td colspan="{{ $firstAffixGroup->affixGroupCouplings->count() + 1 }}">
            <h5 class="py-2 m-0">
                @if ($isNewSeason)
                    {{ __('view_misc.affixes.header_season_start', ['season' => $season->name_long]) }}
                @else
                    {{ __('view_misc.affixes.header_season', ['season' => $season->name_long]) }}
                @endif
            </h5>
        </td>
    </tr>
    <tr>
        <th>
            {{ sprintf(__('view_misc.affixes.start_date'), $seasonService->getUserTimezone()) }}
        </th>
        @foreach($firstAffixGroup->affixGroupCouplings as $affixGroupCoupling)
            <th>
                @if($season->seasonal_affix_id === $affixGroupCoupling->affix_id)
                    {{ __('view_misc.affixes.seasonal') }}
                @else
                    {{ sprintf('+%d', $affixGroupCoupling->key_level) }}
                @endif
            </th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    <?php
    $affixGroupIndex = 0;
    // @formatter:off
    foreach($affixGroups as $index => $arr){
        /** @var Carbon $startDate */
        $startDate = $arr['date_start'];
        /** @var AffixGroup $affixGroup */
        $affixGroup = $arr['affix_group'];
        $isCurrentWeek = $affixGroup->id === $currentAffixGroup->id && $startDate->diffInWeeks($now) <= 1;
        $isFirst = $affixGroupIndex === 0;
        $isLast = $affixGroups->count() - 1 === $affixGroupIndex;

        $timewalkingEvent = $timewalkingEventService->getActiveTimewalkingEventAt($startDate);
        ?>

        @include('misc.table.affixrowtable', [
            'timewalkingEvent' => null,
            'season' => $season,
            'affixGroup' => $affixGroup,
            'isCurrentWeek' => $isCurrentWeek,
            'isFirst' => $isFirst,
            'isLast' => $isLast,
            'startDate' => $startDate,
            'showBottomBorder' => !($timewalkingEvent instanceof TimewalkingEvent),
            'isOdd' => $affixGroupIndex % 2 == 0
            ])

        <?php
        if ($timewalkingEvent !== null) {
            $timewalkingEventAffixGroup = $timewalkingEventService->getAffixGroupAt($timewalkingEvent->expansion, $startDate);
            if( $timewalkingEventAffixGroup !== null ) {
                $timewalkingEventAffixGroup->load('season');
                ?>
                @include('misc.table.affixrowtable', [
                    'timewalkingEvent' => $timewalkingEvent,
                    'season' => $timewalkingEventAffixGroup->season,
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
