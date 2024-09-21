<?php


use App\Models\Npc\Npc;
use App\Models\Affix;

$fortified               = Npc::find(15929);
$fortified->base_health  = 100000;
$tyrannical              = Npc::find(15928);
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
                Forti + Guile health
            </th>
            <th>
                Tyrannical health
            </th>
            <th>
                Tyrannical + Guile health
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
                    {{ $fortified->getScalingFactor($i) }}
                </td>
                <td>
                    {{ $fortified->calculateHealthForKey($i, [Affix::AFFIX_FORTIFIED]) }}
                </td>
                <td>
                    {{ $fortified->calculateHealthForKey($i, [Affix::AFFIX_FORTIFIED, Affix::AFFIX_XALATATHS_GUILE]) }}
                </td>
                <td>
                    {{ $tyrannical->calculateHealthForKey($i, [Affix::AFFIX_TYRANNICAL]) }}
                </td>
                <td>
                    {{ $tyrannical->calculateHealthForKey($i, [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_XALATATHS_GUILE]) }}
                </td>
            </tr>
        @endfor
        </tbody>
    </table>

@endsection
