<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @var Dungeon                                           $contextDungeon
 * @var Carbon                                            $date
 * @var Collection<CombatLogNpcEvent|CombatLogSpellEvent> $events
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$contextDungeon, $date],
    'title'             => __('view_compendium.activity.day.title', ['date' => $date->format('F j, Y')]),
])

@section('header-title')
    {{ __('view_compendium.activity.day.header', ['date' => $date->format('F j, Y')]) }}
@endsection

@section('content')
    @include('compendium.activity.sections.event_list', [
        'events'         => $events,
        'date'           => $date->format('Y-m-d'),
        'contextDungeon' => $contextDungeon,
    ])
@endsection
