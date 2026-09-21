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

    <div class="mb-2">
        <div class="progress mb-1">
            <div id="generate_progress_bar"
                 class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width: 0"
                 aria-valuenow="0"
                 aria-valuemin="0"
                 aria-valuemax="100">
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <small class="text-muted" id="generate_progress_label">–</small>
            <small class="text-muted">
                {{ __('view_admin.tools.dungeonroute.generatetestroutes.remaining') }}:
                <span id="generate_remaining_count">–</span>
                &nbsp;&nbsp;
                {{ __('view_admin.tools.dungeonroute.generatetestroutes.elapsed') }}:
                <span id="generate_timer">00:00:00</span>
                &nbsp;&nbsp;
                {{ __('view_admin.tools.dungeonroute.generatetestroutes.eta') }}:
                <span id="generate_eta">–</span>
            </small>
        </div>
    </div>

    <div class="mb-3 d-flex gap-2 align-items-center">
        <button id="generate_start_btn" class="btn btn-primary">
            <i class="fas fa-plus"></i> {{ __('view_admin.tools.dungeonroute.generatetestroutes.generate') }}
        </button>
        <button id="generate_pause_btn" class="btn btn-warning d-none">
            <i class="fas fa-pause"></i> {{ __('view_admin.tools.dungeonroute.generatetestroutes.pause') }}
        </button>
        <button id="generate_resume_btn" class="btn btn-warning d-none">
            <i class="fas fa-play"></i> {{ __('view_admin.tools.dungeonroute.generatetestroutes.resume') }}
        </button>
        <button id="generate_stop_btn" class="btn btn-secondary d-none">
            <i class="fas fa-stop"></i> {{ __('view_admin.tools.dungeonroute.generatetestroutes.stop') }}
        </button>
        <button id="generate_delete_all_btn" class="btn btn-outline-danger">
            <i class="fas fa-trash"></i> {{ __('view_admin.tools.dungeonroute.generatetestroutes.delete_all') }}
        </button>
        <small class="text-muted">
            {{ __('view_admin.tools.dungeonroute.generatetestroutes.generated_count') }}:
            <span id="generate_generated_count">{{ $generatedCount }}</span>
        </small>
    </div>

    <pre id="generate_log"
         class="bg-dark text-light p-3 rounded"
         style="height: 400px; overflow-y: scroll; white-space: pre-wrap; word-break: break-all;"></pre>
@endsection

@include('common.general.inline', ['path' => 'admin/tools/dungeonroute/generatetestroutes', 'options' => [
    'generateBatchUrl'       => route('admin.tools.dungeonroute.generatetestroutes.generate_batch'),
    'deleteBatchUrl'         => route('admin.tools.dungeonroute.generatetestroutes.delete_batch'),
    'maxCount'               => $maxCount,
    'targetSelector'         => '#generate_target',
    'countSelector'          => '#generate_count',
    'publishedStateSelector' => '#generate_published_state',
    'deleteAllBtnSelector'   => '#generate_delete_all_btn',
    'generatedCountSelector' => '#generate_generated_count',
    'progressBarSelector'    => '#generate_progress_bar',
    'progressLabelSelector'  => '#generate_progress_label',
    'logSelector'            => '#generate_log',
    'startBtnSelector'       => '#generate_start_btn',
    'pauseBtnSelector'       => '#generate_pause_btn',
    'resumeBtnSelector'      => '#generate_resume_btn',
    'stopBtnSelector'        => '#generate_stop_btn',
    'timerSelector'          => '#generate_timer',
    'etaSelector'            => '#generate_eta',
    'remainingCountSelector' => '#generate_remaining_count',
]])
