<?php
/**
 * @var int $count
 * @var int $minId
 * @var int $maxId
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.artisancommands.backfillkillzoneenemyid.title')])

@section('header-title', __('view_admin.tools.artisancommands.backfillkillzoneenemyid.header'))

@section('content')
    <p class="text-muted">{{ __('view_admin.tools.artisancommands.backfillkillzoneenemyid.description') }}</p>

    @if($count === 0)
        <div class="alert alert-success">
            {{ __('view_admin.tools.artisancommands.backfillkillzoneenemyid.nothing_to_do') }}
        </div>
    @else
        <div class="alert alert-info">
            {!! __('view_admin.tools.artisancommands.backfillkillzoneenemyid.rows_remaining', [
                'count' => '<span id="artisan_command_remaining_count">' . number_format($count) . '</span>',
                'min'   => number_format($minId),
                'max'   => number_format($maxId),
            ]) !!}
        </div>

        <div class="mb-2">
            <div class="progress mb-1">
                <div id="artisan_command_progress_bar"
                     class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar"
                     style="width: 0"
                     aria-valuenow="0"
                     aria-valuemin="0"
                     aria-valuemax="100">
                </div>
            </div>
            <div class="d-flex justify-content-between">
                <small class="text-muted" id="artisan_command_progress_label">0 / 0</small>
                <small class="text-muted">
                    {{ __('view_admin.tools.artisancommands.backfillkillzoneenemyid.elapsed') }}:
                    <span id="artisan_command_timer">00:00:00</span>
                    &nbsp;&nbsp;
                    {{ __('view_admin.tools.artisancommands.backfillkillzoneenemyid.eta') }}:
                    <span id="artisan_command_eta">–</span>
                </small>
            </div>
        </div>

        <div class="mb-3" style="display: flex; gap: 8px;">
            <button id="artisan_command_start_btn" class="btn btn-primary">
                <i class="fas fa-play"></i> {{ __('view_admin.tools.artisancommands.backfillkillzoneenemyid.start') }}
            </button>
            <button id="artisan_command_pause_btn" class="btn btn-warning d-none">
                <i class="fas fa-pause"></i> {{ __('view_admin.tools.artisancommands.backfillkillzoneenemyid.pause') }}
            </button>
            <button id="artisan_command_resume_btn" class="btn btn-success d-none">
                <i class="fas fa-play"></i> {{ __('view_admin.tools.artisancommands.backfillkillzoneenemyid.resume') }}
            </button>
            <button id="artisan_command_stop_btn" class="btn btn-danger d-none">
                <i class="fas fa-stop"></i> {{ __('view_admin.tools.artisancommands.backfillkillzoneenemyid.stop') }}
            </button>
        </div>

        <pre id="artisan_command_log"
             class="bg-dark text-light p-3 rounded"
             style="height: 300px; overflow-y: scroll; white-space: pre-wrap; word-break: break-all;"></pre>
    @endif
@endsection

@include('common.general.inline', ['path' => 'admin/tools/artisancommands/backfillkillzoneenemyid', 'options' => [
    'runUrl'                => route('admin.tools.artisancommands.run'),
    'command'               => 'ksg:backfill-kill-zone-enemy-id',
    'minId'                 => $minId,
    'maxId'                 => $maxId,
    'count'                 => $count,
    'chunkSize'             => 50000,
    'progressBarSelector'   => '#artisan_command_progress_bar',
    'progressLabelSelector' => '#artisan_command_progress_label',
    'logSelector'           => '#artisan_command_log',
    'startBtnSelector'      => '#artisan_command_start_btn',
    'pauseBtnSelector'      => '#artisan_command_pause_btn',
    'stopBtnSelector'       => '#artisan_command_stop_btn',
    'resumeBtnSelector'     => '#artisan_command_resume_btn',
    'timerSelector'          => '#artisan_command_timer',
    'etaSelector'            => '#artisan_command_eta',
    'remainingCountSelector' => '#artisan_command_remaining_count',
]])
