@extends('layouts.app')

@section('header-title', __('Time test'))

@section('content')

    <?php
    /** @var \App\Models\GameServerRegion $region */
    $regions = \App\Models\GameServerRegion::all();
    $startOfWeek = \Illuminate\Support\Carbon::now()->startOfYear()->addYear(1)->addWeek(7);
    ?>

    @foreach($regions as $region)
        <h2>
            {{ $region->name }}
        </h2>
        <table width="100%">
            <tr>
                <th>
                    {{ __('Day') }}
                </th>
                <?php for ($hour = 0; $hour < 24; $hour++) { ?>
                <th>
                    {{ $hour }}
                </th>
                <?php } ?>
            </tr>
            <?php for ($day = 1; $day < 8; $day++) {
            $currentDay = $startOfWeek->copy();
            ?>
            <tr>
                <td>
                    {{ $currentDay->addDays($day - 1)->format('D Y-m-d') }}
                </td>
                <?php for ($hour = 0; $hour < 24; $hour++) { ?>
                <td>
                    {{ $region->getAffixGroupAtTime($currentDay->year, $currentDay->month, $currentDay->day, $hour, Auth::user()->timezone)->id }}
                </td>
                <?php } ?>
            </tr>
            <?php } ?>
        </table>
    @endforeach

@endsection