<?php

use App\Models\Dungeon;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, Collection<int, float>> $dungeonAccuracyByFloor
 * @var Collection<int, Dungeon>                $dungeonsById
 */

$dungeonAccuracyByFloor = $dungeonAccuracyByFloor->sortBy(function (Collection $floorAccuracies, int $dungeonId) use (
    $dungeonsById
) {
    $dungeon = $dungeonsById->get($dungeonId);
    if ($dungeon === null) {
        return '';
    }

    return sprintf('%s-%s', $dungeon->expansion->released_at, $dungeon->name);
});
?>

@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.mdt.dungeonmappingversionaccuracy.title')])

@section('header-title', __('view_admin.tools.mdt.dungeonmappingversionaccuracy.header'))

@section('content')
    <div class="container">
        <?php
        $currentExpansionId = null;
        ?>

        @foreach($dungeonAccuracyByFloor as $dungeonId => $floorAccuracies)
                <?php
                if ($floorAccuracies === null || $floorAccuracies->isEmpty()) {
                    continue;
                }

                /** @var Dungeon|null $dungeon */
                $dungeon = $dungeonsById->get($dungeonId);
                if ($dungeon === null) {
                    continue;
                }
                $expansion = $dungeon->expansion;
                ?>

            @if($expansion !== null && $currentExpansionId !== $expansion->id)
                    <?php $currentExpansionId = $expansion->id; ?>
                <h2 class="mt-4 mb-3">{{ __($expansion->name) }}</h2>
            @endif

            <div class="form-group">
                <h3 class="mt-3">{{ __($dungeon->name) }}</h3>
                @foreach($dungeon->floors as $floor)
                    @php
                        $accuracy = $floorAccuracies->get($floor->id);
                    @endphp
                    @if($accuracy !== null)
                        <h4 class="h5 mt-2 mb-1 d-flex align-items-center">
                            <span class="mr-2">{{ __($floor->name) }}</span>
                            <small class="text-muted">({{ number_format($accuracy, 1) }}%)</small>
                        </h4>
                        <div class="progress mb-3">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ max(0, min(100, $accuracy)) }}%;"
                                aria-valuenow="{{ (float)$accuracy }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                                {{ number_format($accuracy, 0) }}%
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
@endsection
