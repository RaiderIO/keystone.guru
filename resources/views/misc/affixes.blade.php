@extends('layouts.app')

@section('header-title', __('Weekly affixes'))

@section('content')
    <table class="affixes_overview_table table-striped" width="100%">
        <thead>
        <tr>
            <th width="20%">
                {{ __('Week') }}
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
        // @TODO This needs to work past one cycle! Ohgodihatetimeandidonthavetimeforthisohtheirony
        $affixGroupIndex = 0;
        $affixGroups = \App\Models\AffixGroup::all();
        $currentWeek = intval(date('W'));
        $currentDay = intval(date('w'));
        foreach($affixGroups as $affixGroup){
        ?>
        <tr class="table_row">
            <?php
            $firstWeek = config('keystoneguru.season_start_week') + $affixGroupIndex;
            $firstWeekTime = strtotime('2018W' . $firstWeek) + (24 * 3600);
            $nextWeek = config('keystoneguru.season_start_week') + $affixGroupIndex + 1;
            $nextWeekTime = strtotime('2018W' . $nextWeek) + (24 * 3600);
            $currentWeekTime = strtotime('2018W' . $currentWeek) + ((24 * 3600) * $currentDay);
            $currentWeekClass = $currentWeekTime > $firstWeekTime && $currentWeekTime <= $nextWeekTime ? 'current_week ' : '';
            ?>
            <td>
                <div class="affix_row first_column {{ $currentWeekClass }}">
                    <span class="d-xl-block d-none">
                        {{ $firstWeek }} + {{ config('keystoneguru.season_start_week') + ($affixGroupIndex + 1) }}
                        (~{{ date('Y/M/d', $firstWeekTime) }})
                    </span>
                    <span class="d-xl-none d-block">
                        ~{{ date('Y/M/d', $firstWeekTime) }}
                    </span>
                </div>
            </td>
            <?php
            $affixIndex = 0;
            foreach($affixGroup->affixes as $affix) {
            $class = $currentWeekClass;
            $class .= count($affixGroup->affixes) - 1 === $affixIndex ? 'last_column ' : '';
            $class .= ($affixGroupIndex === 0) ? 'first_row ' : '';
            $class .= count($affixGroups) - 1 === $affixGroupIndex ? 'last_row ' : '';
            ?>
            <td>
                <div class="affix_row {{ $class }}">
                    <div class="row no-gutters">
                        <div class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->name) }}"
                             data-toggle="tooltip"
                             title="{{ $affix->name }}"
                             style="height: 24px;">
                        </div>
                        <div class="col d-lg-block d-none pl-1">
                            {{ $affix->name }}
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

    <div class="mt-4 col-12 text-center">
        <p>
            {!!  __('For more information about affixes and what they do, please visit <a href="https://mythicpl.us/">https://mythicpl.us/ <i class="fas fa-external-link-alt"></i></a>') !!}
        </p>
    </div>
@endsection