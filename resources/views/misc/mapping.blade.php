<?php

use App\Models\Dungeon;
use App\Models\Floor\Floor;

?>
@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('view_misc.mapping.title')])

@section('header-title', __('view_misc.mapping.header'))

@section('content')
    <h2>{{ __('view_misc.mapping.enemy_forces_mapping_progress') }}</h2>
    <div class="row">
        <div class="col-lg-2 font-weight-bold">
            {{ __('view_misc.mapping.dungeon') }}
        </div>
        <div class="col-lg-4 font-weight-bold">
            {{ __('view_misc.mapping.enemy_forces_assigned') }}
        </div>
        <div class="col-lg-4 font-weight-bold">
            {{ __('view_misc.mapping.npcs_assigned_to_enemies') }}
        </div>
        <div class="col-lg-2 font-weight-bold">
            {{ __('view_misc.mapping.teeming') }}
        </div>
    </div>
    @foreach(Dungeon::with(['npcs'])->active()->get() as $dungeon )
            <?php /** @var Dungeon $dungeon */ ?>
        <div class="row">
            <div class="col-lg-2">
                {{ __($dungeon->name) }}
            </div>
            <div class="col-lg-4">
                <div class="progress">
                    @php($percent = $dungeon->enemy_forces_mapped_status['percent'])
                    @php($total = $dungeon->enemy_forces_mapped_status['total'])
                    @php($curr = $total - $dungeon->enemy_forces_mapped_status['unmapped'])
                    <div class="progress-bar" style="width: {{ $percent }}%;" role="progressbar"
                         aria-valuenow="{{ $percent }}" aria-valuemin="0"
                         aria-valuemax="100">
                        <span class="text-left">
                        {{ __('view_misc.mapping.enemy_forces') . sprintf(' %s/%s %d%%', $curr, $total, $percent) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="progress">
                        <?php
                        $totalEnemies           = 0;
                        $totalUnassignedEnemies = 0;
                        $hasTeemingEnemy        = false;
                        foreach ($dungeon->floors()->with(['dungeon'])->get() as $floor) {
                            /** @var Floor $floor */
                            $totalEnemies           += $floor->enemies()->count();
                            $totalUnassignedEnemies += $floor->enemies()->whereNull('npc_id')->count();
                            $hasTeemingEnemy        = $hasTeemingEnemy || $floor->enemies()->where('teeming', 'visible')->count() > 0;
                        }
                        ?>
                    @php($curr = $totalEnemies - $totalUnassignedEnemies)
                    @php($percent = $totalEnemies === 0 ? 0 : ($curr / $totalEnemies) * 100)
                    @php($total = $totalEnemies)
                    <div class="progress-bar" style="width: {{ $percent }}%;" role="progressbar"
                         aria-valuenow="{{ $percent }}" aria-valuemin="0"
                         aria-valuemax="100">
                        <span class="text-left">
                        {{ __('view_misc.mapping.npcs_assigned') . sprintf(' %s/%s %d%%', $curr, $total, $percent) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                {{ html()->checkbox($dungeon->key . '_teeming', $hasTeemingEnemy, 1)->disabled() }}
            </div>
        </div>
    @endforeach
@endsection
