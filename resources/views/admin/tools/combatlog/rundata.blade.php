@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.combatlog.rundata.title')])

@section('header-title', __('view_admin.tools.combatlog.rundata.header'))

@section('content')
    <p class="text-muted">{{ __('view_admin.tools.combatlog.rundata.description') }}</p>

    <table class="table table-sm table-striped mb-4">
        <thead>
        <tr>
            <th>{{ __('view_admin.tools.combatlog.rundata.column_keep') }}</th>
            <th>{{ __('view_admin.tools.combatlog.rundata.column_season') }}</th>
            <th class="text-right">{{ __('view_admin.tools.combatlog.rundata.column_total') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($seasonStats as $stat)
            <tr>
                <td>
                    <input type="checkbox"
                           class="season-keep-checkbox"
                           value="{{ $stat->season }}"
                           id="season_{{ $stat->season }}">
                </td>
                <td>
                    <label for="season_{{ $stat->season }}" class="mb-0">
                        {{ $stat->season ?? __('view_admin.tools.combatlog.rundata.unknown_season') }}
                    </label>
                </td>
                <td class="text-right">{{ number_format($stat->total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        {{ __('view_admin.tools.combatlog.rundata.prune_warning') }}
    </div>

    <div class="mb-2">
        <div class="progress mb-1">
            <div id="rundata_progress_bar"
                 class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width: 0"
                 aria-valuenow="0"
                 aria-valuemin="0"
                 aria-valuemax="100">
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <small class="text-muted" id="rundata_progress_label">–</small>
            <small class="text-muted">
                {{ __('view_admin.tools.combatlog.rundata.remaining') }}:
                <span id="rundata_remaining_count">–</span>
                &nbsp;&nbsp;
                {{ __('view_admin.tools.combatlog.rundata.elapsed') }}:
                <span id="rundata_timer">00:00:00</span>
            </small>
        </div>
    </div>

    <div class="mb-3" style="display: flex; gap: 8px;">
        <button id="rundata_start_btn" class="btn btn-danger">
            <i class="fas fa-play"></i> {{ __('view_admin.tools.combatlog.rundata.start') }}
        </button>
        <button id="rundata_pause_btn" class="btn btn-warning d-none">
            <i class="fas fa-pause"></i> {{ __('view_admin.tools.combatlog.rundata.pause') }}
        </button>
        <button id="rundata_resume_btn" class="btn btn-warning d-none">
            <i class="fas fa-play"></i> {{ __('view_admin.tools.combatlog.rundata.resume') }}
        </button>
        <button id="rundata_stop_btn" class="btn btn-secondary d-none">
            <i class="fas fa-stop"></i> {{ __('view_admin.tools.combatlog.rundata.stop') }}
        </button>
    </div>

    <pre id="rundata_log"
         class="bg-dark text-light p-3 rounded"
         style="height: 300px; overflow-y: scroll; white-space: pre-wrap; word-break: break-all;"></pre>
@endsection

@include('common.general.inline', ['path' => 'admin/tools/combatlog/rundata', 'options' => [
    'pruneBatchUrl'          => route('admin.tools.combatlog.rundata.prune_batch'),
    'seasonsFormSelector'    => '.season-keep-checkbox:checked',
    'minId'                  => $minId,
    'maxId'                  => $maxId,
    'chunkSize'              => 500,
    'progressBarSelector'    => '#rundata_progress_bar',
    'progressLabelSelector'  => '#rundata_progress_label',
    'logSelector'            => '#rundata_log',
    'startBtnSelector'       => '#rundata_start_btn',
    'pauseBtnSelector'       => '#rundata_pause_btn',
    'resumeBtnSelector'      => '#rundata_resume_btn',
    'stopBtnSelector'        => '#rundata_stop_btn',
    'timerSelector'          => '#rundata_timer',
    'remainingCountSelector' => '#rundata_remaining_count',
]])
