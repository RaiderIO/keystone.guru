<?php

use App\Models\Dungeon;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, Season>  $seasons
 * @var Season|null              $currentSeason
 * @var Collection<int, Dungeon> $dungeons
 * @var array<int, string>       $publishedStates
 * @var int                      $maxCount
 * @var int                      $generatedCount
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.dungeonroute.generatetestroutes.title')])

@section('header-title', __('view_admin.tools.dungeonroute.generatetestroutes.header'))

@section('content')
    <p class="text-muted">{{ __('view_admin.tools.dungeonroute.generatetestroutes.description') }}</p>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="generate_target" class="form-label">{{ __('view_admin.tools.dungeonroute.generatetestroutes.target') }}</label>
            <select id="generate_target" class="form-select">
                <optgroup label="{{ __('view_admin.tools.dungeonroute.generatetestroutes.target_seasons') }}">
                    @foreach($seasons as $season)
                        <option value="season-{{ $season->id }}"
                                data-dungeon-ids="{{ json_encode($season->dungeons->pluck('id')) }}"
                            @selected($season->id === $currentSeason?->id)>
                            {{ __($season->id === $currentSeason?->id ? 'view_admin.tools.dungeonroute.generatetestroutes.target_season_current' : 'view_admin.tools.dungeonroute.generatetestroutes.target_season', [
                                'season' => sprintf('%s %s', __($season->expansion->name), $season->name),
                                'count'  => $season->dungeons->count(),
                            ]) }}
                        </option>
                    @endforeach
                </optgroup>
                <optgroup label="{{ __('view_admin.tools.dungeonroute.generatetestroutes.target_dungeons') }}">
                    @foreach($dungeons as $dungeon)
                        <option value="dungeon-{{ $dungeon->id }}" data-dungeon-ids="{{ json_encode([$dungeon->id]) }}">
                            {{ sprintf('%s (%s)', __($dungeon->name), __($dungeon->expansion->name)) }}
                        </option>
                    @endforeach
                </optgroup>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label for="generate_count" class="form-label">{{ __('view_admin.tools.dungeonroute.generatetestroutes.count', ['max' => $maxCount]) }}</label>
            <input id="generate_count" type="number" class="form-control" min="1" max="{{ $maxCount }}" value="5">
        </div>
        <div class="col-md-3 mb-3">
            <label for="generate_published_state" class="form-label">{{ __('view_admin.tools.dungeonroute.generatetestroutes.published_state') }}</label>
            <select id="generate_published_state" class="form-select">
                @foreach($publishedStates as $publishedState)
                    <option value="{{ $publishedState }}" @selected($publishedState === \App\Models\PublishedState::WORLD)>
                        {{ __(sprintf('js.publish_state_title_%s', $publishedState)) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-3 d-flex gap-2 align-items-center">
        <button id="generate_start" class="btn btn-primary">
            <i class="fas fa-plus"></i> {{ __('view_admin.tools.dungeonroute.generatetestroutes.generate') }}
        </button>
        <button id="generate_delete_all" class="btn btn-outline-danger">
            <i class="fas fa-trash"></i> {{ __('view_admin.tools.dungeonroute.generatetestroutes.delete_all') }}
        </button>
        <small class="text-muted" id="generate_generated_count">
            {{ __('view_admin.tools.dungeonroute.generatetestroutes.generated_count', ['count' => $generatedCount]) }}
        </small>
    </div>

    <div class="progress mb-2">
        <div id="generate_progress_bar" class="progress-bar" role="progressbar" style="width: 0"
             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>

    <div id="generate_log" class="bg-dark text-light p-3 rounded font-monospace small"
         style="height: 400px; overflow-y: scroll; white-space: pre-wrap;"></div>
@endsection

@include('common.general.inline', ['path' => 'admin/tools/dungeonroute/generatetestroutes', 'options' => [
    'generateUrl'            => route('admin.tools.dungeonroute.generatetestroutes.generate'),
    'deleteBatchUrl'         => route('admin.tools.dungeonroute.generatetestroutes.delete_batch'),
    'maxCount'               => $maxCount,
    'targetSelector'         => '#generate_target',
    'countSelector'          => '#generate_count',
    'publishedStateSelector' => '#generate_published_state',
    'startBtnSelector'       => '#generate_start',
    'deleteAllBtnSelector'   => '#generate_delete_all',
    'generatedCountSelector' => '#generate_generated_count',
    'progressBarSelector'    => '#generate_progress_bar',
    'logSelector'            => '#generate_log',
    'translations'           => [
        'invalidCount'     => __('view_admin.tools.dungeonroute.generatetestroutes.log_invalid_count', ['max' => $maxCount]),
        'generating'       => __('view_admin.tools.dungeonroute.generatetestroutes.log_generating'),
        'dungeonDone'      => __('view_admin.tools.dungeonroute.generatetestroutes.log_dungeon_done'),
        'deleteAllConfirm' => __('view_admin.tools.dungeonroute.generatetestroutes.delete_all_confirm'),
        'deleting'         => __('view_admin.tools.dungeonroute.generatetestroutes.log_deleting'),
        'deleted'          => __('view_admin.tools.dungeonroute.generatetestroutes.log_deleted'),
        'generatedCount'   => __('view_admin.tools.dungeonroute.generatetestroutes.generated_count'),
        'done'             => __('view_admin.tools.dungeonroute.generatetestroutes.log_done'),
        'error'            => __('view_admin.tools.dungeonroute.generatetestroutes.log_error'),
    ],
]])
