<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @var Dungeon                                                               $contextDungeon
 * @var LengthAwarePaginator<int, string>                                     $dates
 * @var array<string, Collection<int, CombatLogNpcEvent|CombatLogSpellEvent>> $eventsByDay
 * @var Collection<int, Dungeon>                                              $gameVersionDungeons
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$contextDungeon],
    'title'             => __('view_compendium.activity.index.title'),
    'dungeonContextLinks' => $gameVersionDungeons->mapWithKeys(fn (Dungeon $dungeon) => [
        $dungeon->key => route('compendium.activity', ['dungeon' => $dungeon])
    ]),
])

@section('header-title')
    {{ __('view_compendium.activity.index.header') }}
@endsection

@section('content')
    @if($dates->isEmpty())
        <p class="text-muted">{{ __('view_compendium.activity.index.empty') }}</p>
    @else
        @foreach($dates->items() as $date)
                <?php /** @var string $date */ ?>
            <div class="mb-4">
                @include('compendium.activity.sections.event_list', [
                    'events'         => $eventsByDay[$date],
                    'date'           => $date,
                    'contextDungeon' => $contextDungeon,
                    'limit'          => 15,
                ])
            </div>
        @endforeach

        {{ $dates->links() }}
    @endif
@endsection
