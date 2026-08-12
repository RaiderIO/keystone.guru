<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @var Dungeon                                                $contextDungeon
 * @var Carbon                                                 $date
 * @var Collection<int, CombatLogNpcEvent|CombatLogSpellEvent> $events
 * @var Collection<int, Dungeon>                               $gameVersionDungeons
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$contextDungeon, $date],
    'title'             => __('view_compendium.activity.day.title', ['date' => $date->format('F j, Y')]),
    // To that dungeon's activity overview - the same day does not necessarily exist for it
    'dungeonContextLinks' => $gameVersionDungeons->mapWithKeys(fn (Dungeon $dungeon) => [
        $dungeon->key => route('compendium.activity', ['dungeon' => $dungeon])
    ]),
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
