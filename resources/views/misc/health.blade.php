<?php
$fortified               = \App\Models\Npc\Npc::find(15929);
$fortified->base_health  = 100000;
$tyrannical              = \App\Models\Npc\Npc::find(15928);
$tyrannical->base_health = 100000;

?>
@extends('layouts.sitepage', ['cookieConsent' => false, 'showAds' => false, 'analytics' => false, 'title' => __('view_misc.health.title')])

@section('header-title', __('view_misc.health.header'))

@section('content')

    <table class="w-100">
        <thead>
        <tr>
            <th>
                Keystone level
            </th>
            <th>
                Base modifier
            </th>
            <th>
                Forti health
            </th>
            <th>
                Forti + Thundering health
            </th>
            <th>
                Tyrannical health
            </th>
            <th>
                Tyrannical + Thundering health
            </th>
        </tr>
        </thead>
        <tbody>
        @for($i = 2; $i <= 30; $i++)
            <tr>
                <td>
                    {{ $i }}
                </td>
                <td>
                    {{ $fortified->getScalingFactor($i, false, false, false) }}
                </td>
                <td>
                    {{ $fortified->calculateHealthForKey($i, true, false, false) }}
                </td>
                <td>
                    {{ $fortified->calculateHealthForKey($i, true, false, true) }}
                </td>
                <td>
                    {{ $tyrannical->calculateHealthForKey($i, true, false, false) }}
                </td>
                <td>
                    {{ $tyrannical->calculateHealthForKey($i, true, false, true) }}
                </td>
            </tr>
        @endfor
        </tbody>
    </table>

@endsection
