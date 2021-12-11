<?php
/**
 * @var \App\Models\Timewalking\TimewalkingEvent $timewalkingEvent
 * @var \App\Models\AffixGroup $affixGroup
 * @var \Illuminate\Support\Carbon|null $startDate
 * @var boolean $showStartDate
 * @var boolean $isCurrentWeek
 * @var boolean $isFirst
 * @var boolean $isLast
 * @var boolean $showTopBorder
 * @var boolean $showBottomBorder
 * @var boolean $isOdd
 */

$startDate = $startDate ?? null;
$timewalkingEvent = $timewalkingEvent ?? null;
$isCurrentWeek = $isCurrentWeek ?? false;
$isFirst = $isFirst ?? false;
$isLast = $isLast ?? false;
$showTopBorder = $showTopBorder ?? true;
$showBottomBorder = $showBottomBorder ?? true;
$isOdd = $isOdd ?? false;

$timewalkingClasses = $timewalkingEvent !== null ? 'timewalking ' . $timewalkingEvent->expansion->shortname : '';
?>
<tr class="table_row {{ $isOdd ? 'odd' : 'even' }} {{ $timewalkingClasses }}">
    <?php
    // Current week if we found the current affix group for this region
    $currentWeekClass = $isCurrentWeek ? 'current_week ' : '';
    $topBorderClass = $showTopBorder ? 'border_top ' : '';
    $bottomBorderClass = $showBottomBorder ? 'border_bottom ' : '';
    ?>
    <td class="first_column {{ $currentWeekClass . $topBorderClass . $bottomBorderClass }}">
        <div class="affix_row">
            @if($timewalkingEvent !== null)
{{--                <img src="{{ $timewalkingEvent->expansion->iconfile->getURL() }}" style="width: 32px; height: 32px;"/>--}}
                {{ sprintf(__('%s timewalking'), __($timewalkingEvent->expansion->name)) }}
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
    $affixIndex = 0;
    foreach($affixGroup->affixes as $affix) {
    $lastColumn = count($affixGroup->affixes) - 1 === $affixIndex;
    $class = $currentWeekClass . $topBorderClass . $bottomBorderClass;
    $class .= $lastColumn ? 'last_column ' : '';
    $class .= $isFirst ? 'first_row ' : '';
    $class .= $isLast ? 'last_row ' : '';
    ?>
    <td class="{{ $class }}">
        <div class="affix_row">
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
    ?>
</tr>
