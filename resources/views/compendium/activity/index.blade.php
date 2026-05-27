<?php

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @var Dungeon                                                          $contextDungeon
 * @var LengthAwarePaginator<string>                                     $dates
 * @var array<string, Collection<CombatLogNpcEvent|CombatLogSpellEvent>> $eventsByDay
 */
?>
@extends('layouts.sitepage', [
    'breadcrumbsParams' => [$contextDungeon],
    'title'             => __('view_compendium.activity.index.title'),
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
            <div class="mb-5">
                @include('compendium.activity.sections.event_list', [
                    'events'         => $eventsByDay[$date],
                    'date'           => $date,
                    'contextDungeon' => $contextDungeon,
                ])
            </div>
        @endforeach

        {{ $dates->links() }}
    @endif
@endsection
