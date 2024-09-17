<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Season;
use App\Models\Timewalking\TimewalkingEvent;
use Illuminate\Support\Carbon;

/**
 * @var TimewalkingEvent $timewalkingEvent
 * @var Season           $season
 * @var AffixGroup       $affixGroup
 * @var Carbon|null      $startDate
 * @var boolean          $showStartDate
 * @var boolean          $isCurrentWeek
 * @var boolean          $isFirst
 * @var boolean          $isLast
 * @var boolean          $showTopBorder
 * @var boolean          $showBottomBorder
 * @var boolean          $isOdd
 */

$startDate        ??= null;
$timewalkingEvent ??= null;
$isCurrentWeek    ??= false;
$isFirst          ??= false;
$isLast           ??= false;
$showTopBorder    ??= true;
$showBottomBorder ??= true;
$isOdd            ??= false;

$timewalkingClasses = $timewalkingEvent !== null ? 'text-white timewalking ' . $timewalkingEvent->expansion->shortname : '';
?>
<tr class="table_row {{ $isOdd ? 'odd' : 'even' }} {{ !$affixGroup->confirmed ? 'unconfirmed' : '' }} {{ $timewalkingClasses }}">
    <?php
    // Current week if we found the current affix group for this region
    $currentWeekClass  = $isCurrentWeek ? 'current_week ' : '';
    $topBorderClass    = $showTopBorder ? 'border_top ' : '';
    $bottomBorderClass = $showBottomBorder ? 'border_bottom ' : '';
    ?>
    <td class="first_column {{ $currentWeekClass . $topBorderClass . $bottomBorderClass }}">
        <div class="affix_row">
            @if($timewalkingEvent !== null)
                {{--                <img src="{{ $timewalkingEvent->expansion->iconfile->getURL() }}" style="width: 32px; height: 32px;"/>--}}
                {{ sprintf(__('view_misc.table.affixrowtable.expansion_timewalking'), __($timewalkingEvent->expansion->name)) }}
            @else
                <span>
                    {{ $startDate->format('Y/M/d') }}
                </span>
                <span class="d-xl-inline d-none">
                    {{ $startDate->format(' @ H\h') }}
                </span>
            @endif
        </div>
    </td>
    <?php
    foreach ($affixGroup->affixes as $i => $affix){
        $isSeasonalAffix = $affix->id === $season->seasonal_affix_id;
        $lastColumn      = $i === count($affixGroup->affixes) - 1;
        $class           = $currentWeekClass . $topBorderClass . $bottomBorderClass;
        $class           .= $lastColumn ? 'last_column ' : '';
        $class           .= $isSeasonalAffix ? 'seasonal ' : '';
        $class           .= $isFirst ? 'first_row ' : '';
        $class           .= $isLast ? 'last_row ' : '';
        ?>
    <td class="{{ $class }}">
        @if($affix !== null)
            <div class="affix_row">
                <div class="row no-gutters">
                    <div
                        class="col-auto select_icon class_icon affix_icon_{{ \Str::slug($affix->key, '_') }}"
                        data-toggle="tooltip"
                        title="{{ __($affix->description) }}"
                        style="height: 24px;">
                    </div>
                    <div class="col d-lg-block d-none pl-1">
                        @if($isSeasonalAffix && $affixGroup->seasonal_index !== null)
                            {{ sprintf(__('affixes.seasonal_index_preset'), __($affix->name), $affixGroup->seasonal_index + 1) }}
                        @else
                            {{ __($affix->name) }}
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </td><?php
         }
         ?>
</tr>
